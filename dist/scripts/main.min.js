function get_data() {
    $.getJSON( "get_data.php", function( data ) {
        $('.totalInstalls').html(data.total_installs);
        $('h3.weekly span').html(data.last_7_days);
        $('h3.monthly span').html(data.last_30_days);
        $('h4.weekly span').html(data.weekly + '%');
        $('h4.monthly span').html(data.monthly + '%');
        if(data.weekly_dir == 'down') {
            $('.weekly .fa-arrow-circle-up').addClass('hide');
            $('.weekly .fa-arrow-circle-down').removeClass('hide');
        }
        else {
            $('.weekly .fa-arrow-circle-up').removeClass('hide');
            $('.weekly .fa-arrow-circle-down').addClass('hide');
        }
        if(data.monthly_dir == 'down') {
            $('.monthly .fa-arrow-circle-up').addClass('hide');
            $('.monthly .fa-arrow-circle-down').removeClass('hide');
        }
        else {
            $('.monthly .fa-arrow-circle-up').removeClass('hide');
            $('.monthly .fa-arrow-circle-down').addClass('hide');
        }
        
    });

}
get_data();
var now = new Date();
var delay = 60 * 60 * 1000; // 1 hour in msec
var start = delay - (now.getMinutes() * 60 + now.getSeconds()) * 1000 + now.getMilliseconds();

setTimeout(function doSomething() {
    get_data();
   // schedule the next tick
   setTimeout(doSomething, delay);
}, start);
