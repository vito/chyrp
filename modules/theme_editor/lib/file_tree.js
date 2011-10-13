$(document).ready( function() {
    // Hide all subfolders at startup
    $(".theme-file-tree").find("ul").hide();
    // Getting GET['file'] value
    var file = $.getUrlVar('file');
    // Expand active directories
    $('.theme-file-tree').find("a[href$='" +file+ "']").addClass('active').parents('ul').css('display', 'block');
    // Expand/collapse on click
    $(".pft-dir a").click( function() {
        $(this).parent().find("ul:first").slideToggle("medium");
        if( $(this).parent().attr('className') == "pft-dir" ) return false;
    });
});

$.extend({
    getUrlVars: function() {
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');

        for(var i = 0; i < hashes.length; i++) {
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    },
    getUrlVar: function(name) {
        return $.getUrlVars()[name];
    }
});
