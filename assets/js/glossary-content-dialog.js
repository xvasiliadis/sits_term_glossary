/**
 * @file
 * Js for Glossary Content .
 */

(function ($, Drupal, drupalSettings) {
    'use strict';
    Drupal.behaviors.GlossaryContent = {
        attach: function (context) {
            if (context === document) {
                // Set up stuff here.
            }

            $('.glos-term').once().on('click', function (event) {
                event.preventDefault();
                var termId = $(this).data('gterm');
                var termText = $(this).text();
                // Var string = '<div id="glossary-dialog" title="Term definition"><div id="glossary-dialog-inner">Loading ..</div></div>';.
                $('#glossary-dialog').dialog({
                    autoOpen: true,
                    resizable: false,
                    modal: true,
                    close: function () {
                        $('#glossary-dialog-inner').html('');
                    },
                    buttons: {
                        Close: function () {
                            $(this).dialog('close');
                            // $(this).dialog('destroy').remove();
                            $('#glossary-dialog-inner').html('');
                        }
                    }
                });
                // Call Ajax here.
                var url = '/web/glossary-get-term-by-id/' + termId;
                $.ajax({
                    url:url,
                    dataType: "json",
                    type: "GET",
                    success: function (data) {
                        $('#glossary-dialog-inner').html('');
                        if (data.name && data.description) {
                            $('#glossary-dialog-inner').append('<b>' + data.name + '</b><br/>');
                            $('#glossary-dialog-inner').append(data.description);

                            var event = new CustomEvent('glossary_modal_show', { detail: data });
                            document.dispatchEvent(event);

                           /*
                            HOOK for Modal if using "hook_term_glossary_alter_result"
                            use to append custom data to the modal.
                            document.addEventListener('glossary_modal_show', function (e) {
                                console.log(e);
                                //alert(detail.name);
                                $('#glossary-dialog-inner').once().append('<h2>test</h2>');
                            }, false);

                            */

                        }
                        else {
                            $('#glossary-dialog-inner').html('No results found for' + termText);
                        }
                    }
                });

            });

        }
    };
})(jQuery, Drupal, drupalSettings);
