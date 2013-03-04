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
          alert(data.error);
        }
        pl.isLocked = data.ret;
        if (data.ret) {
          // islocked = true
          pl.elem.text(pl.lang.dounlock);
        } else {
          pl.elem.text(pl.lang.dolock);
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
  var elem_li = jQuery('<li/>').append(elem_a);
  jQuery('#p-namespaces ul').append(elem_li);
  pl.elem.click(pl.onClick);
  pl.refresh();
};

jQuery(document).ready(pl.initialize);

