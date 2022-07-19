;( function( $, _, undefined ) {
    'use strict';
    ips.createModule( 'ips.dtprofiler.debug', function() {
        var respond = function( elem, options, e ) {
            var el = $( elem );
            if ( !el.data( '_debugObj' ) ) {
                var d = _debugObj( el );
                d.init( el.data( 'url' ), el );
                el.data( '_debugObj', d );
            }
            $( 'body' ).bind( 'beforeunload', function() {
                var obj = el.data( '_debugObj' );
                obj.abort();
            } );
        };
        ips.ui.registerWidget( 'dtprofilerdebug', ips.dtprofiler.debug );
        return {
            respond: respond,
        };
    } );
    var _debugObj = function() {
        var ajax = null,
            current = null,
            aurl,
            burl,
            el,
            socket= null,
            init = function( url, elem ) {
            burl = url;
            aurl = burl + '&do=debug';
            el = elem;
            ajax = ips.getAjax();
            _debug();
            elem.find( 'li.dtProfilerClear' ).on( 'click', function( e ) {
                let el = $( this );
                let parent = el.parent( 'ul' );
                let parentId = parent.attr( 'id' );
                let pid = parentId.substr( 0, parentId.length - 5 );
                _clear();
                $( '#' + pid ).find( '.dtprofilerCount' ).html( 0 ).attr( 'data-count', 0 );

                parent.find( 'li' ).not( '.dtProfilerClear' ).each( function() {
                    $( this ).remove();
                } );

                parent.removeClass( 'isOpen' ).
                    slideUp().
                    parent().
                    find( 'i.dtprofilearrow' ).
                    removeClass( 'fa-rotate-180' );
            } );
        },
            _socket = () => {
                try {
                    ips.toolbox.main.getSocket().on('debug', function(data) {
                        _process(data);
                    });
                    setTimeout(function(){
                        if(!ips.toolbox.main.getSocket().connected){
                            throw new Error('no sockets');
                        }
                    },20000);
                }catch(error){
                    _ajax();
                }
            },
            _ajax = () => {
                current = ajax({
                    type: 'POST',
                    data: 'last=' + $('#elProfiledebug', el).attr('data-last'),
                    url: aurl,
                    dataType: 'json',
                    bypassRedirect: true,
                    success: function(data) {
                        _process(data);
                    },
                    complete: function(data) {
                        _debug();
                    },
                    error: function(data) {
                    },
                });
            },
            _clear = function() {
            ajax( {
                type: 'GET',
                url: burl + '&do=clearAjax',
                bypassRedirect: true,
            } );
        }, abort = function() {
            current.abort();
        },
         _debug = () => {

            try{
                if(!ips.getSetting('cj_debug_sockets')){
                    throw new Error('Sockets Disabled!');
                }
                _socket();
            }
            catch(error){
                _ajax();
            }

        },
        _process = (data)=>{
            var countEl = el.find('#elProfiledebug').
                find('.dtprofilerCount');

            if (!data.hasOwnProperty('error')) {
                $('#elProfiledebug_list', el).append(data.items);
                var count = Number(countEl.attr('data-count'));
                count = Number(data.count) + count;
                countEl.html(count).attr('data-count', count);
                countEl.parent().addClass('dtprofilerFlash');
                $('#elProfiledebug', el).
                    attr('data-last', data.last);
                if ($('#elProfiledebug', el).hasClass('ipsHide')) {
                    $('#elProfiledebug', el).removeClass('ipsHide');
                }
                countEl.parent().addClass('dtprofilerFlash');
            }
        };

        return {
            init: init,
            abort: abort,
        };
    };
}( jQuery, _ ) );
