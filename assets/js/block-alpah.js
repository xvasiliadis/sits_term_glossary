/**
 * @file
 * Js for block.
 */

(function ($, Drupal, drupalSettings) {
    'use strict';
    Drupal.behaviors.AlphaBlock = {
        attach: function (context) {
            if (context === document) {
                // Set up stuff here.
            }

            // Search box.
            $('#glossary-by-term-btn').once().on('click', function (event) {
                event.preventDefault();
                var theText = $('#glossary-search-text').val();
                if (theText) {
                    searchTerm(theText);
                }
                else {
                    $('#glossary-alpha-results').html('Please enter a search term.');
                    return false;
                }
            });
            // End search box.
            $('#glossary-alpha-block li a').once().on('click', function (event) {
                event.preventDefault();
                removeActiveClass();
                var letter = $(this).text();
                var item = $(this);
                item.addClass('active');
                searchLetter(letter);
            });

            function removeActiveClass() {
                $('#glossary-alpha-block li a').each(function () {
                   if ($(this).hasClass('active')) {
                       $(this).removeClass('active');
                   }
                });
            }

            function searchTerm(term) {
                var url = '/glossary-search-term?t=' + encodeURI(term);
                $.ajax({
                    url:url,
                    dataType: "json",
                    type: "GET",
                    success: function (data) {
                        console.log(data);
                        handleResults(data);
                    }
                });
            }

            function searchLetter(letter) {
               var url = '/glossary-search-letter/' + letter;
                $.ajax({
                    url:url,
                    dataType: "json",
                    type: "GET",
                    success: function (data) {
                        handleResults(data);
                    }
                });
            }

            function handleResults(data) {
                var resultsEle = $('#glossary-alpha-results');
                resultsEle.html('');
                if (data.message) {
                    resultsEle.html(data.message);
                }
                else {
                    console.log(data);
                    for (var i in data) {
                        var item = data[i];
                        var string = "<div class='media' data-term='" + item.tid + "'>";
                        string += '<b>' + item.name + '</b><br/>';
                        string += item.description;
                        string += "</div>";
                        resultsEle.append(string);
                    }
                }
            }

        }
    };
})(jQuery, Drupal, drupalSettings);
