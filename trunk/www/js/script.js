
function reload( form )
{
    while ( form.tagName != 'FORM' )
    {
        form = form.parentNode;
    }
    
    form.submit();    
}

if ( typeof( prepareFocus ) == 'function' )

    prepareFocus();

$( document ).ready(
    function()
    {
        var location_menu = null;
        var location_jbutton = null;

        var body = "#body";  
        var name = "#menu_block_right";  
        var regexp;

        var id_button_view_feedback = 'div_view_feedback';

        var jbutton_view_feedback, submit_form = null;

        var regexp_action = new RegExp( '/([^#\/]+)#?(.*)$', 'gi' );

        var regexp_internal_anchor = new RegExp( '#(.*)', 'gi' );

        var regexp_target = new RegExp( '[0-9]+$', 'gi' );

        var results_action = regexp_action.exec( location.href );

        var results_internal_anchor = regexp_internal_anchor.exec( location.href );

        if ( $( name ) && $( name ).css( 'top' ) )
        {
            $( name ).css( 'top', '175px' ); 
 
            location_menu = parseInt( $( name ).css( 'top' ).substring(
                0,
                $( name ).css( 'top' ).indexOf( 'px' ) )
            );

            $(window).scroll(function () {  
                var offset = location_menu+$(document).scrollTop()+"px";  
                $(name).animate({top:offset},{duration:500,queue:false});  
            });
        }
    
        $( 'a.menu_item' ).click(
            function()
            {
                var form;

                form = $("#" + this.getAttribute('href').substring(1));

                if (!this.getAttribute('href').match(/_[0-9]+/))
                {
                    $(".form_container").css('display', 'none');
    
                    form.css('display', 'block');
                }
            }
        );

        if (
            results_internal_anchor != null && results_internal_anchor[1] &&
            regexp_target.exec( results_internal_anchor[1] ) == null &&
            ! results_internal_anchor[1].match( /body$/ ) &&
            ! results_internal_anchor[1].match( /^_/ )
        )
        {
            if ( $( '#' + results_internal_anchor[1] ).length != 0 )
            {
                form = $( '#' + results_internal_anchor[1] );

                $( '.form_container' ).css( 'display', 'none' );

                form.css( 'display', 'block' );
            }
        }

        var condition_process_form_send_feeback = results_action !== null &&
            (
                results_action[1] &&
                regexp_target.exec( results_action[1] ) == null &&
                results_action[1] === 'send-feedback'
            ) || (
                results_action[2] &&
                regexp_target.exec( results_action[2] ) == null &&
                results_action[2] === 'send_feedback'                
            )
        ;

        if ( ! condition_process_form_send_feeback )
        {
            jbutton_view_feedback = $( '#' + id_button_view_feedback );
            
            submit_form = function( event ) {
                event.preventDefault();

                $.ajax({
                    data: $( '#send_feedback form' ).serialize(),
                    url: $( '#send_feedback form' ).attr('action'),
                    success: function( data )
                    {
                        form_response = $( $.parseXML( data )).find( 'div#send_feedback' );
                        
                        /**
                        *
                        * Replace the form layout from the response returned
                        *
                        */

                        $( 'div#send_feedback' ).html( form_response.html() );

                        /**
                        *
                        * Attach the form submission event to the newly
                        * replaced form
                        *
                        */

                        if ( $( 'input#submit_feedback' ) )

                            $( 'input#submit_feedback' ).click( submit_form );
                    },
                    type: 'POST'
                });
            };

            dialog_options = {
                autoOpen: false,
                height: 500,
                modal: true,
                open: function()
                {   
                    $( 'div#send_feedback form' ).submit(
                        function ( event ) {
                            event.preventDefault();
                        }
                    )

                    $( 'input#submit_feedback' ).click( submit_form );                    
                },
                position: ['center', 30],
                title: '',
                width: 800
            };

            $('#view_feedback_dialog').
                load( '/send-feedback div#send_feedback').
                dialog( dialog_options )
            ;
            
            jbutton_view_feedback.click(
                function() { $('#view_feedback_dialog').dialog( 'open' ) }
            );

            if ( jbutton_view_feedback.css( 'top' ) )
            {
                location_jbutton =
                    parseInt(
                        jbutton_view_feedback.css( 'top' ).
                        substring( 0, jbutton_view_feedback.css( 'top' ).indexOf( 'px' ) )
                    )
                ;
                
                $( window ).scroll(
                    function ()
                    {  
                        var offset = location_jbutton + $( document ).scrollTop() + 'px';
                        jbutton_view_feedback.animate(
                            {top:offset},
                            {duration:500, queue:false}
                        );
                    }
                );
            }
        }
    }
)