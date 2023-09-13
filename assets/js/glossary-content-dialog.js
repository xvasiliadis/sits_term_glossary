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
            $('.glos-term').once('glossaryListenner').on('click', function (event) {
                event.preventDefault();
                var termId = $(this).data('gterm');
                var termText = $(this).text();
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
                            $('#glossary-dialog-inner').html('');
                        }
                    }
                });
                // Call Ajax here.
                var url = Drupal.url('glossary-get-term-by-id/' + termId);
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
