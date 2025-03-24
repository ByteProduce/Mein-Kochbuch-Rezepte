jQuery(document).ready(function($) {
    $('#dark-mode-toggle').click(function() {
        $('body').toggleClass('dark-mode');
        localStorage.setItem('darkMode', $('body').hasClass('dark-mode') ? 'enabled' : 'disabled');
    });
    if (localStorage.getItem('darkMode') === 'enabled') {
        $('body').addClass('dark-mode');
    }
});