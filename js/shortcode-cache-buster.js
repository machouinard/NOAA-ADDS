(function ($) {
    var apts, hours, show_metar, show_taf, show_pireps, show_station_info, radial_dist, title;
    $(function () {
        console.log($);

        var a = $('.awfn-shortcode');
        console.log(a);
        $.each(a, function (i, v) {
            console.log(v);
            var $this = $(this);
            var atts = $(this).data('atts');
            console.log( atts );
            $(this).html('working...');
            $.ajax({
                url: ajax_url,
                type: 'post',
                data: {
                    action: 'weather_shortcode',
                    atts: atts
                },
                success : function( resp ) {
                    console.log( resp );
                    $this.html(resp.data);
                },
                error : function( x ) {
                    alert('error: ' + x );
                }
            })
        });

    });
})(jQuery);