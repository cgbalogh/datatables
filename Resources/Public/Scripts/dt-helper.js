/**
 * dt-helper.js
 * 
 * JS helpers for datatables
 * 
 */
var obj = null;

var button;
var uid;
var rowid;
var linkrefid;
var _delete;
var _delete_text;
var datatables = [];
var columnDefinitions = [];
var _col = [];
var lastAjaxCall = {};
var callUserFunc = null;
var test = null;

$.fn.dataTable.ext.errMode = 'none';
 
$(document).ready(function() {
    activateDatatables();
    
    $('#modalConfirm').on('show.bs.modal', function (event) {
      button = $(event.relatedTarget);      // Button that triggered the modal
      uid = button.data('uid');             // Extract info from data-* attributes
      uidlist = $('[data-uidlist]').attr('data-uidlist');
      rowid = button.data('rowid'); 
      linkrefid = button.data('linkrefid');
      _delete = $("._delete").attr('delete');
      _delete_text = $("._delete_text").attr('delete_text');
      
      var $modal = $(this)
      $modal.find('.modal-title').text(_delete + '?');
      $modal.find('.modal-body').html(_delete_text.replace('%s', uid));
      
      if (! uid && typeof(uidlist) != 'undefined') {
          $modal.find('.modal-body').html(_delete_text.replace('%s', uidlist));
      }
      $('#modalConfirm').attr('uid', uid);
      $('#modalConfirm').attr('rowid', rowid);
      $('#modalConfirm').attr('linkrefid', linkrefid);
    });

    $('#modalConfirm').click(function() {
        var id = $(document.activeElement).attr('id'); 
        var $modalConfirm = $('#modalConfirm');
        if (id == 'button-modalConfirm') {
            linkrefid = $modalConfirm.attr('linkrefid');
            rowid = $modalConfirm.attr('rowid');
            uid = $modalConfirm.attr('uid');
            uidlist = $('[data-uidlist]').attr('data-uidlist');

            href = $('#' + linkrefid).attr('href');

            if (typeof(uidlist) != 'undefined') {
//                $modal.find('.modal-body').html(_delete_text.replace('%s', uidlist));
                href = href.replace('UIDLIST', uidlist);
            }
            // call url to delete record and fade out
            // TODO: at the moment the record will be faded out no matter what result is returned.
            $.get( href );
            if ((typeof(uidlist) != 'undefined') & (uidlist != '') ) {
                params = rowid.split('_');
                uidlistarray = uidlist.split(',');
                for (i=0; i < uidlistarray.length; i++) {
                    rowid = params[0] + '_' + uidlistarray[i];
                    $('#' +  rowid).fadeOut();
                }
                $('.tx_datatables-bulkDelete').prop('checked', false);
            } else {
                $('#' +  rowid).fadeOut();
            }
        }
    });
    
    // hide header and footer if necessary
    setTimeout(function() { 
        $('.tx_datatables-noheader1').parent().find('.fg-toolbar').hide();
        $('.tx_datatables-noheader1 tfoot').hide();
    }, 1);
});

/**
 * disable sorting of last column in table view
*/
function activateDatatables() {
    var columnCount = 0;
    var cObjUid = '';
    var ajaxUrl = '';
    var dontsort = [];
    var addclass = [];
    var myId = '';
    $('.tx_datatables-datatable').each(function(){
        $datatable = $(this);
        
        // count th-elements to get number of columns
        columnCount = $datatable.find('thead tr th').length - 1;
        
        // get cObjUid (id of content element) from attribute
        cObjUid = parseInt($datatable.attr('cobjuid'));
        
        noHeader = ( $("._noHeader", 'div[settings=' + $datatable.attr('id') + ']').attr('noheader') == '1');
        disableGlobalSearch = ( $("._disableglobalsearch", 'div[settings=' + $datatable.attr('id') + ']').attr('disableglobalsearch') == '1');
        order = $('[data-datatables-order]').data('datatablesOrder');
        
        if (order == '') {
            order = [[0,"asc"]];
        }
        
        
        
        
        // create url for list action
        // TODO: make ajax id configurable in backend
        ajaxUrl = 
            $("._baseUrl").attr('baseurl') +
            'index.php?cobj=' + cObjUid +
            '&type=' + $("._ajaxCallId").attr('ajaxcallid') + 
            '&dtfilter=' + $datatable.attr('dtfilter') + 
            '&&tx_datatables_datatable[data]=' + $datatable.attr('data') + 
            '&tx_datatables_datatable[action]=list&tx_datatables_datatable[controller]=Datatable'+
            '&L=' + getUrlParam('L') +
            '&id=' + getUrlParam('id')
            ;

        // create array for columns not be sorted indicated by attribute dtdontsort
        dontsort = [];
        addclass = [];
        for(var i=0;i<=columnCount;i++) {
            addclass.push();
            if ($datatable.find('thead tr th:eq('+i+')').attr('dtdontsort') == "1") {
                dontsort.push(i);
            }
        }

        // set column defs
        var columnDefs =[
            {"targets": dontsort, "orderable": false, "searchable": false}
        ];

        $datatable.find('thead th').each(function(){
            columnDef = {"name": $(this).attr('_property'), "targets": $(this).index() }
            columnDefs.push(columnDef);
        });

        columnDefinitions.push(columnDefs);

        // invoke datatables
        var table = $datatable.DataTable({
            "language": {
              "url": "/typo3conf/ext/datatables/Resources/Public/Scripts/datatables." + $('html').attr('lang') + ".json"  
            },
            "bProcessing": false,
            "order": order,
            "searching": (! noHeader ),
            "paging": ! noHeader,
            "info": ! noHeader,
            "ajax": {
                "url": ajaxUrl,
                "type": 'GET',
                "error": function(jqXHR, error, thrown){
                    window.clearInterval(refreshDataTables);
                    if (typeof(jqXHR.responseText) != 'undefined') {
                        document.write(jqXHR.responseText);
                        $('.media-body').append('<h4 style="margin-top: 1em;">Datatables tried to call the following URI by ajax call</h4>');
                        $('.media-body').append('<div class="media-addendum" style="word-wrap: break-word; max-width: 520px;"><a href="'+ajaxUrl+'">' + ajaxUrl + '</a></div>');
                    } else if (typeof(modalMessageInvoke) == 'function') {
                        modalMessageInvoke('Error','Error calling ' + ajaxUrl, 'Close');
                    } else {
                        document.write('Error calling ' + ajaxUrl);
                    }
                }
            },
            "serverSide": true,
            "fnRowCallback": function( nRow, aData, iDisplayIndex ) {
                var c = $(this).closest('table.dataTable').find('thead tr th').length - 1;
                for(var i=0;i<=c;i++) {
                    $('> td:eq('+i+')', nRow).addClass( $(this).closest('table.dataTable').find('thead tr th:eq('+i+')').attr('dtclass') );
                    $('> td:eq('+i+')', nRow).addClass( $(this).closest('table.dataTable').find('thead tr th:eq('+i+')').attr('dtalign') );
              }
              
              return nRow;
            },
            "fnDrawCallback": function() { drawCallbackFn()},
            "responsive" : true,
            "lengthMenu": [[10, 20, 50, 100, 200], [10, 20, 50, 100, 200]],
            "stateSave": true,
           
            // apply the dontsort array
            "columnDefs": columnDefs
        });

        // $datatable.on( 'error.dt', function ( e, settings, techNote, message ) {
        //            // document.write(jqXHR.responseText);
        //    test = e;
        //    alert(e.responseText);
        // });

        $datatable.on( 'xhr.dt', function (e, settings, data) {
            $('#' + $(this).attr('id') + '-sql').html(data.sql);
        });

        $datatable.on( 'preXhr.dt', function (e, settings, data) {
            var datatablesId = settings['sTableId'];
            var _ajaxUrl = settings['ajax']['url'];
            // test = settings['ajax']['url'];
            $.ajax({
              beforeSend: function(jqXhr, settings ) { 
                  lastAjaxCall[datatablesId] = {};
                  lastAjaxCall[datatablesId].url = _ajaxUrl;
                  lastAjaxCall[datatablesId].data = data;
                  // lastAjaxCall.url = ajaxUrl; lastAjaxCall.data = data; 
              }  
            });
            // $('.dtsearch-enable').show();
        });            
        $datatable.on('responsive-resize.dt', function ( e, settings, visibilities ) {
            var counter = 0;
            $('.dtsearch-row td', $(this)).each(function(){
                if (! visibilities[counter]) {
                   $(this).css('display','none');
                } else {
                   $(this).css('display','table-cell');
                }
                counter++;
            });
        });
            
        // bulkdelete checks    
        $datatable.find(".tx_datatables-bulkDelete").on('click',function() { // bulk checked
            var status = this.checked;
            $datatable.find(".tx_datatables-deleteRow").each( function() {
                $(this).prop("checked",status);
            });
        });      

        // save table reference to array
        datatables.push($datatable);
        var searchable = false;
        
        // add search field
        $datatable.find('thead [searchcolumn=1]').each( function () {
            searchable = true;
            $(this).html( '<input class="dt-headersearch" type="text" style="width: 100%;" />' );
        } );    

        var $select = '';
        $datatable.find('thead [searchcolumn=2]').each( function () {
            searchable = true;
            $select = $('<select style="width: 100%;"><option value=""></option></select>')
                .appendTo ( $(this).empty() )
                .on( 'change', function () {
                    columnNumber = $(this).parent().index();
                    $(this).closest('.tx_datatables-datatable').DataTable().column(columnNumber).search($(this).val()).draw();
                });
            dataArray = $(this).attr('searchoptions').split(',');
            $(dataArray).each( function ( index, data ) {
                valueLabelPairArray = data.split(':');
                $select.append( '<option value="' + valueLabelPairArray[0] + '">' + valueLabelPairArray[1] + '</option>' )
            });
        });
        
        var refreshDataTables = setInterval(function (id) { 
            if (! $('#' + id).hasClass('dt-paused')) {
                table.ajax.reload(null, false);
            }
        }, 300000, $datatable.attr('id') ); 

        var options = {
            callback: function ( value ) { 
                columnNumber = $(this).parent().index();
                $(this).closest('.tx_datatables-datatable').DataTable().column(columnNumber).search($(this).val()).draw();
                // console.log('TypeWatch callback: (' + (this.type || this.nodeName) + ') ' + value + ' ' + columnNumber); 
            },
            wait: 500,
            highlight: true,
            allowSubmit: false,
            captureLength: 2
        };    

        if (searchable) {
            $datatable.find('thead input').typeWatch( options );
            $('.dtsearch-row-header').addClass('dtsearch-enable');
        }

        /*
        $datatable.find('thead td input').on( 'keyup', function () {
            columnNumber = parseInt($(this).parent().attr('_id'));
            $(this).closest('.tx_datatables-datatable').DataTable().column(columnNumber).search($(this).val()).draw();

        */
       
        if (disableGlobalSearch) {
            setTimeout(function(){
                $datatable.prev().find('.dataTables_filter').hide();
            }, 10);
        }
       
        // Apply the search
        if (! noHeader) {
            table.columns().every( function () {
                this.search('');
            });
        }
    });
}

/**
 * drawCallbackFn
 * 
 * if the attribute autoAjax is set in a tag with class _autoAjax attached the autoAjax feature will be in voked
 * 
 * @returns {undefined}
 */
function drawCallbackFn () {
    // activateInlineEditing();
    
    if ($("._autoAjax").attr('autoAjax') == '1') {
        autoAjax();
    }
    
    $('[dt-mode="dt-bubbleup-td"]').each(function(){
        $(this).closest('td').addClass($(this).attr('class'));
        $(this).closest('td').attr('style',$(this).attr('style'));
        $(this).parent().html($(this).html());
    });

    $('[dt-mode="dt-bubbleup-tr"]').each(function(){
        $(this).closest('tr').addClass($(this).attr('class'));
        $(this).closest('tr').attr('style',$(this).attr('style'));
        $(this).parent().html($(this).html());
    });
    $('.dtsearch-enable').show();

    if (noHeader) {
        $datatable.parent().find('.fg-toolbar,.dtsearch-row').hide();
    }
 
    execFN = makeFn(callUserFunc);
    try {if (typeof(execFN) == 'function') execFN();}
    catch (error) {alert('Error #1434874899: execFN failed!');}
}

/**
 * 
 * @returns {undefined}
 */
function getSelectedRecords ( context ) {
    if ( $('.tx_datatables-deleteRow:checked', context).length > 0 ){  // at-least one checkbox checked
        var ids = [];
        $('.tx_datatables-deleteRow').each(function(){
            if($(this).is(':checked')) { 
                ids.push($(this).val());
            }
        });
        return ids.toString(); 
    }
}


function getUrlParam( sParam ) {
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] === sParam) {
            return sParameterName[1];
        }
    }
    return '';
}

/**
 * function makeInt
 * 
 * @param {type} value
 * @returns {unresolved}
 */
function makeInt ( value ) {
	if (typeof(value) == 'undefined') {
		return null;
	} else if (value == '') {
		return null;
	} else {
		return parseInt(value);
	}
}

/**
 * 
 * @returns {String}
 */
function printUrl ( datatableId ) {
    data = lastAjaxCall[datatableId].data;
    url = lastAjaxCall[datatableId].url;
    url = url.replace('[action]=list','[action]=print') + '&' + $.param(data);
    url = url.replace('dtfilter=&&','dtfilter=&');
    return url;
}

/**
 * 
 * @returns {String}
 */
function exportUrl ( datatableId ) {
    data = lastAjaxCall[datatableId].data;
    url = lastAjaxCall[datatableId].url;
    url = url.replace('[action]=list','[action]=export') + '&' + $.param(data);
    return url;
}


function activateInlineEditing() {
    $datatable.find('.dt-editinline').dblclick(function(){
        if (! $(this).hasClass('dt-editinline-active')) {
            $(this).addClass('dt-editinline-active');
            reference = $(this).find('span').attr('reference');
            $(this).attr('data-innerhtml',$(this).html()); 
            
            var FORM = $('<form></form>');
            $(this).html('');
            $(this).append(FORM);
            $form = $(this).find('form').first();
            $form.append('<input class="dt-editinline-input" type="text" name="dt-inline-data" value="'+$(this).find('span').html()+'" />');
            $form.append('<input class="" type="hidden" name="dt-inline-reference" value="'+reference+'" />');
            $('.dt-editinline-input').focus();
            $('.dt-editinline-input').select();
            $(this).unbind('dblclick');
            
            $('.dt-editinline-input').keypress(function(e){
                e.stopImmediatePropagation()
                if (e.which == 13) {
                    value = $(this).val();
                    $td = $(this).parent();
                    $td.html($td.attr('data-innerhtml'));
                    $td.find('span').html(value);
                    $td.removeClass('dt-editinline-active');
                    
                    var postVars = $(this).closest('form').serialize();
                    url = 'saveChanges';
                    alert(postVars);
                    
                    
                }
                if (e.which == 27) {
                    alert('asc');
                    $td = $(this).parent();
                    $td.html($td.attr('data-innerhtml'));
                    $td.removeClass('dt-editinline-active');
                }
            });
        }
    });
    
}

/**
 * 
 * @param {type} $table
 * @returns {undefined}
 */
function reloadTable($table) {
    $table.DataTable().ajax.reload(null, false);
}
