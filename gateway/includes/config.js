function BasicConfig() {
	//Forms Config
	$timepickerDefault = {
		timeOnlyTitle: "Escoja la Hora",
		timeText:"Hora",
		hourText:"Hora",
		minuteText:"Minuto",
		secondText:"Segundo",
		currentText:"Ahora",
		closeText:"Cerrar",
		stepMinute: 10
		
	};
	$.datepicker.regional['es'] = {
			closeText: 'Cerrar',
			prevText: '&#x3c;Ant',
			nextText: 'Sig&#x3e;',
			currentText: 'Hoy',
			monthNames: ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
			'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
			monthNamesShort: ['Ene','Feb','Mar','Abr','May','Jun',
			'Jul','Ago','Sep','Oct','Nov','Dic'],
			dayNames: ['Domingo','Lunes','Martes','Mi&eacute;rcoles','Jueves','Viernes','S&aacute;bado'],
			dayNamesShort: ['Dom','Lun','Mar','Mi&eacute;','Juv','Vie','S&aacute;b'],
			dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','S&aacute;'],
			weekHeader: 'Sm',
			firstDay: 1,
			isRTL: false,
			showMonthAfterYear: false,
			yearSuffix: ''};
	$.datepicker.setDefaults($.datepicker.regional['es']);
	
	$('.date').datepicker({
		dateFormat: 'yy-mm-dd',
		showButtonPanel: true,
		changeYear: true,
		changeMonth: true,
		yearRange: '1940:2010',
		gotoCurrent: true
	});
		
	$("#overlay").dialog({
		autoOpen: false,
		width: 700,
		modal: true,
		resizable: true,
		title: $('#overlay h3').html()
	});
	
	$( "input:submit, input:button, ul.action a").button();
	

	/*$(".__radio").buttonset();*/

	
	//Behaviors
	$("form:not(.filter) :input:visible:enabled:first").focus();
};

var myBindings = Array ();
var isCtrl = false;
$(document).keyup(
	function (e) {	if(e.which == 16) isCrtl=false;}
);
$(document).keydown(
	function (e) {
	    if(e.which == 17) {
	    	isCtrl = true;
	    } else if(isCtrl==true) {
	    	var character;
	    	character = String.fromCharCode(e.which);
			for(key in myBindings)
			{
			   if(key == character) {
				  eval(myBindings[key]);
				  return false;
			   }
			}
		}
	 }
);

$(document).ready(function(){
	BasicConfig();
	if(typeof ExtendedConfig == 'function') {
		ExtendedConfig();
	}
});
