var map;
var infoWindow;

function initialize() {
   //var mapOptions = ;

   map = new google.maps.Map(document.getElementById('map-canvas'), {
      center: {lat: 39.828175, lng: -98.5795},
      zoom: 4
   });

   infoWindow = new google.maps.InfoWindow();
   google.maps.event.addListener(map, 'click', function() {
      infoWindow.close();
   });
   displayMarkers();
}

google.maps.event.addDomListener(window, 'load', initialize);

function displayMarkers(){
   for (var i = 0; i < articleLocationData.posts.length; i++){

      var latlng = new google.maps.LatLng(articleLocationData.posts[i].latitude, articleLocationData.posts[i].longitude);
      var postInfo = articleLocationData.posts[i];

      createMarker(latlng, postInfo);
   }
}

function createMarker(latlng, postInfo) {
   var marker = new google.maps.Marker({
      map: map,
      position: latlng,
      title: name,
      icon: 'https://lh3.ggpht.com/hx6IeSRualApBd7KZB9s2N7bcHZIjtgr9VEuOxHzpd05_CZ6RxZwehpXCRN-1ps3HuL0g8Wi=w9-h9'
   });

   google.maps.event.addListener(marker, 'click', function() {
      var imageMarkup = '';
      if(typeof postInfo.image_url != 'undefined') {
      	imageMarkup = '<div class="iw_image"><img src="' + postInfo.image_url + '" align="left" style="height:100px;" /></div>';
      }
      var iwContent = '<div id="iw_container">' +
            '<div class="iw_title">' + postInfo.post_title + '</div>' +
            '<div class="iw_content">' + imageMarkup +
            '<div class="iw_excerpt">' + postInfo.excerpt + '</div>' +
            '<br /><a href="' + postInfo.permalink + '" style="float:right;">Read Full Story &raquo;</a></div></div>';
      infoWindow.setContent(iwContent);
      infoWindow.open(map, marker);
   });
}