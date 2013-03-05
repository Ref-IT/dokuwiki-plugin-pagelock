var pl = { };
pl.conf = { };
pl.lang = { };
pl.isLocked = false;

pl.refresh = function() {
  jQuery.post(
      DOKU_BASE + 'lib/exe/ajax.php',
      { 'call' : 'pagelock_islocked', 'id' : pl.conf.id },
      function(data) {
        if (data.msg) {
          alert(data.msg);
        }
        if (data.error) {
          pl.elem_li.hide();
          alert(data.error);
        }
        if (data.error || data.unsupported) {
          pl.elem_li.hide();
        } else {
          pl.elem_li.show();
          pl.isLocked = data.ret;
          if (data.ret) {
            // islocked = true
            pl.elem.text(pl.lang.dounlock);
          } else {
            pl.elem.text(pl.lang.dolock);
          }
        }
      }
  );
}

pl.onClick = function() {
  if (pl.isLocked) {
    pl.onClickDoUnlock();
  } else {
    pl.onClickDoLock();
  }
  return false;
}

pl.onClickDoUnlock = function() {
  jQuery.post(
      DOKU_BASE + 'lib/exe/ajax.php',
      { 'call' : 'pagelock_removelock', 'id' : pl.conf.id },
      function(data) {
        pl.refresh();
      }
  );
  return false;
}

pl.onClickDoLock = function() {
  jQuery.post(
      DOKU_BASE + 'lib/exe/ajax.php',
      { 'call' : 'pagelock_addlock', 'id' : pl.conf.id },
      function(data) {
        pl.refresh();
      }
  );
  return false;
}

pl.initialize = function() {
  if (!pagelock_config) return;
  pl.lang = LANG.plugins.pagelock;
  pl.conf = pagelock_config;
  pl.elem = jQuery('<span/>');
  var elem_a = jQuery('<a href=""/>').append(pl.elem);
  pl.elem_li = jQuery('<li/>').append(elem_a);
  jQuery('#p-namespaces ul').append(pl.elem_li);
  pl.elem.click(pl.onClick);
  pl.refresh();
};

jQuery(document).ready(pl.initialize);

