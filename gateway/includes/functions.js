/** Functions for the message Dialog **/
$.fn.clearForm = function() {
  return this.each(function() {
    var type = this.type, tag = this.tagName.toLowerCase();
    if (tag == 'form')
      return $(':input',this).clearForm();
    if (type == 'text' || type == 'password' || tag == 'textarea')
      this.value = '';
    else if (type == 'checkbox' || type == 'radio')
      this.checked = false;
    else if (tag == 'select')
      this.selectedIndex = -1;
  });
};

/** Functions for the overlay **/
function closeOverlay() {
  $('#overlay').attr('innerHTML','');
  $('#overlay').dialog('close');
}

function showOverlay() {
  $('#overlay').dialog('open');
  BasicConfig();
}

/**
 * @todo fix languaje issues
 * @todo fix url issues
 * @param to_root
 * @param image
 * @return void
 */
function overlayImage(to_root, image) {
	content ="<img src='"+image+"' /> <ul class='action'><li><a href='javascript:closeOverlay();' title='Volver'><img src='"+to_root+"/images/toolbars/undo.png' alt='Back' /></a></li></ul>";
	showOverlay(content); 
}

/** Paging functions for Listings **/
function change_page(element, url){
  var page_number = valSelect(element);
  url = url.replace('replace_with_page_number', page_number);
  window.location = url;
}

function change_page_size(element, url){
  var page_size = valSelect(element);
  url = url.replace('replace_with_page_size', page_size);
  window.location = url;
}

/** Generic buttons to get the value of various HTML Input Elements **/
function valRadioButton(radio_button)
{
  for (i=radio_button.length-1; i > -1; i--) {
    if (radio_button[i].checked) {
      option = i;
    }
  }
  return radio_button[option].value;
}

function valSelect(select)
{
  return select.options[select.selectedIndex].value;
}

/** Generic Form functions **/
function focusOnFirst() {
  if (document.forms.length > 0) {
	for (var i=0; i < document.forms[0].elements.length; i++) {
      var oField = document.forms[0].elements[i];
      if (oField.type != "hidden") {
        oField.focus();
        return;
      }
	}
  }
}
