!function(t){t(function(){var e=t(".adds-weather-wrapper"),a="1"===widgetOptions.awfn_debug;t.each(e,function(e,n){var i=t(this),o=i.data("instance");t.ajax({url:ajax_url,type:"post",data:{action:"weather_widget",security:widgetOptions.security,instance:o},success:function(t){a&&console.log("widget ajax success"),i.html(t.data)},error:function(t){a&&console.log("widget error posting to weather_widget")}})})})}(jQuery);