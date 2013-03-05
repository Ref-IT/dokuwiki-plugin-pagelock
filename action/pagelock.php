<?php
/**
 * DokuWiki Plugin pagelock (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Michael Braun <michael-dev@fami-braun.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'action.php';

class action_plugin_pagelock_pagelock extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler &$controller) {
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'handle_tpl_metaheader_output');
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax');
    }

    public function handle_ajax(&$event, $param) {
        if (class_exists("action_plugin_ipgroup")) {
          $plugin = new action_plugin_ipgroup();
          $plugin->start($event, $param);
        }
        if (!auth_isadmin()) {
            return;
        }

        $call = $event->data;
        if(method_exists($this, "handle_ajax_$call")) {
           $json = new JSON();

           header('Content-Type: application/json');
           try {
             $ret = $this->{"handle_ajax_$call"}();
           } catch (Exception $e) {
             $ret = Array("file" => __FILE__, "line" => __LINE__, "error" => $e->getMessage(), "trace" => $e->getTraceAsString(), "url" => $this->ep_url);
           }
           print $json->encode($ret);
           $event->preventDefault();
        }
    }

    private function getLockString($ID) {
        global $AUTH_ACL;
        // find lowest level for this page
        $ID = trim($ID, ':');
        $ids = explode(':', $ID);
        $matches = Array();
        for ($i=count($ids)-1; $i >= 0; $i--) {
          $subids = array_slice($ids, 0, $i);
          $subid = trim(implode(':', $subids).':*',':');
          $matches = preg_grep('/^'.preg_quote($subid, '/').'\s+(\S+)\s+(\S+)/ui', $AUTH_ACL);
          if(count($matches) > 0) {
            break;
          }
        }
        $hasALLrule = false;
        $res = Array();
        foreach ($matches as $line) {
          $matchLine = Array();
          if (preg_match('/^(\S+)\s+(\S+)\s+(\S+)/', $line, $matchLine) === FALSE) {
            return NULL;
          }
          $hasALLrule = ($hasALLrule || (strtoupper($matchLine[2]) == '@ALL'));
          if ($matchLine[3] > 1) {
            $res[] = sprintf("%s\t%s\t1", $ID, $matchLine[2]);
            $res[] = sprintf("%s:*\t%s\t1", $ID, $matchLine[2]);
          }
        }
        if (!$hasALLrule) {
          return NULL;
        }
  
        return $res;
    }

    private function handle_ajax_pagelock_islocked() {
        global $INPUT, $AUTH_ACL;

        $ID = cleanID($INPUT->post->str('id'));
        $expected = $this->getLockString($ID);
        if ($expected === NULL) return Array("unsupported" => 1);
        $intersect = array_unique(array_intersect($AUTH_ACL, $expected));
        return Array("ret" => (count($expected) == count($intersect)));
    }

    private function handle_ajax_pagelock_addlock() {
        global $INPUT, $config_cascade;

        $ID = cleanID($INPUT->post->str('id'));
        $AUTH_ACL = file($config_cascade['acl']['default'],  FILE_IGNORE_NEW_LINES );
        $expected = $this->getLockString($ID);
        if ($expected === NULL) return Array("error" => $this->getLang("unsupported"));
        io_saveFile($config_cascade['acl']['default'], join(DOKU_LF,array_merge($AUTH_ACL, $expected)).DOKU_LF);
    }

    private function handle_ajax_pagelock_removelock() {
        global $INPUT, $config_cascade;

        $ID = cleanID($INPUT->post->str('id'));
        $AUTH_ACL = file($config_cascade['acl']['default'],  FILE_IGNORE_NEW_LINES );
        $expected = $this->getLockString($ID);
        if ($expected === NULL) return Array("error" => $this->getLang("unsupported"));
        $AUTH_ACL = array_diff($AUTH_ACL, $expected);
        io_saveFile($config_cascade['acl']['default'], join(DOKU_LF,$AUTH_ACL).DOKU_LF);
    }

    public function handle_tpl_metaheader_output(Doku_Event &$event, $param) {
        global $INFO, $ACT;
        if (($ACT == 'show') && auth_isadmin()) {
          $config = array(
              'id' => $INFO['id'],
              'base' => DOKU_BASE.'lib/plugins/pagelock/'
          );
  
          $json = new JSON();
          $this->include_script($event, 'var pagelock_config = '.$json->encode($config));
          $path = 'scripts/pagelock.js';
          $this->link_script($event, DOKU_BASE.'lib/plugins/pagelock/'.$path);
        }
    }

    private function include_script($event, $code) {
        $event->data['script'][] = array(
            'type' => 'text/javascript',
            'charset' => 'utf-8',
            '_data' => $code,
        );
    }

    private function link_script($event, $url) {
        $event->data['script'][] = array(
            'type' => 'text/javascript',
            'charset' => 'utf-8',
            'src' => $url,
        );
    }
}

// vim:ts=4:sw=4:et:
