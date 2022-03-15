
// // deleteguard modal exit btn
// let exitModalDeleteGuard = document.querySelector("#exit-modal-deleteguard")
// exitModalDeleteGuard.addEventListener('click', e => {
//     let deleteguardModal = document.querySelector('.modal-deleteguard');
//     deleteguardModal.style.display = "none";
// })


// for company without location
let addhereContainer = document.querySelector('#addhere');
let addnew = document.querySelector('#addnewmodal');

// create input fields
addnew.addEventListener('click', (e)=>{

    // input name="name"
    let inputName = document.createElement('input');
    inputName.setAttribute('type', 'text');
    inputName.className = "name";
    inputName.setAttribute('placeholder', 'name');
    inputName.setAttribute('onchange', 'computeTotal(this)');
    inputName.setAttribute('autocomplete', 'off');

    // input name="price"
    let inputPrice = document.createElement('input');
    inputPrice.setAttribute('type', 'text');
    inputPrice.className = "price";
    inputPrice.setAttribute('placeholder', '00.00');
    inputPrice.setAttribute('autocomplete', 'off');
    
    let createDiv = document.createElement('div');
    createDiv.classList.add('position-container');

    // append in div first
    createDiv.appendChild(inputName);
    createDiv.appendChild(inputPrice);

    // append created elements
    addhereContainer.appendChild(createDiv);
    addhereContainer.appendChild(createDiv);

    let names = document.querySelectorAll('.name');
    let prices = document.querySelectorAll('.price');

    for(let i = 0; i < names.length; i++){
        if(i > 0){
            names[i].setAttribute('name', `name${i}`);
            prices[i].setAttribute('name', `price${i}`);
        }
    }
    
});


let inputLength = document.querySelector('.length');
function computeTotal(x){
   if(x.value !== ""){
      if(!x.classList.contains("blocked")){
        let total = parseInt(inputLength.value) + parseInt(1);
        inputLength.value = total;
        x.classList.add('blocked');
      }
   }
}

// detect location
let currPosition = [];

navigator.geolocation.getCurrentPosition((pos) => {
    currPosition.push(pos.coords.longitude);
    currPosition.push(pos.coords.latitude);
    
    let userLongitude = document.querySelector('#longitude');
    let userLatitude = document.querySelector('#latitude');

    let userLongitudeAddModal = document.querySelector('#longitude-addmodal');
    let userLatitudeAddModal = document.querySelector('#latitude-addmodal');

    mapboxgl.accessToken = 'pk.eyJ1IjoiamVsbHliZWFucy1zbHkiLCJhIjoiY2t4NmVnYXU5MnJkNjJ1cW92ZDN1b3hndiJ9.FgwIbfJQOkbfbc1OtJHv2Q';
    const map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/satellite-streets-v9',
        center: currPosition,
        zoom: 18
    });

    const mapAddModal = new mapboxgl.Map({
        container: 'map-addmodal',
        style: 'mapbox://styles/mapbox/satellite-streets-v9',
        center: currPosition,
        zoom: 18
    });

    const marker = new mapboxgl.Marker().setLngLat(currPosition).addTo(map); 
    const marker2 = new mapboxgl.Marker().setLngLat(currPosition).addTo(mapAddModal); 

    function add_marker(event){
        var coordinates = event.lngLat;
        userLongitude.value = coordinates.lng;
        userLatitude.value = coordinates.lat;

        userLongitudeAddModal.value = coordinates.lng;
        userLatitudeAddModal.value = coordinates.lat;

        marker.setLngLat(coordinates).addTo(map);
        marker2.setLngLat(coordinates).addTo(mapAddModal);

        // for distance
        const map_b = new mapboxgl.Map({
            container: 'map_b',
            style: 'mapbox://styles/mapbox/satellite-streets-v9',
            center: [coordinates.lng, coordinates.lat],
            zoom: 18
        });

        // for modal distance
        const map_bAddModal = new mapboxgl.Map({
            container: 'map_b-addmodal',
            style: 'mapbox://styles/mapbox/satellite-streets-v9',
            center: [coordinates.lng, coordinates.lat],
            zoom: 18
        });

        const map_b_size = document.querySelector('.map_b_size');
        const map_b_sizeAddModal = document.querySelector('.map_b_size-addmodal');

        // GeoJSON object to hold our measurement features
        const geojson = {
            'type': 'FeatureCollection',
            'features': []
        };

        // Used to draw a line between points
        const linestring = {
            'type': 'Feature',
            'geometry': {
                'type': 'LineString',
                'coordinates': []
            }
        };


        map_b.on('load', () => {
            map_b.addSource('geojson', {
                'type': 'geojson',
                'data': geojson
            });

            // Add styles to the map
            map_b.addLayer({
                id: 'measure-points',
                type: 'circle',
                source: 'geojson',
                paint: {
                    'circle-radius': 5,
                    'circle-color': '#000'
                },
                filter: ['in', '$type', 'Point']
            });

            map_b.addLayer({
                id: 'measure-lines',
                type: 'line',
                source: 'geojson',
                layout: {
                    'line-cap': 'round',
                    'line-join': 'round'
                },
                paint: {
                    'line-color': '#000',
                    'line-width': 2.5
                },
                filter: ['in', '$type', 'LineString']
            });

            map_b.on('click', (e) => {
                const features = map_b.queryRenderedFeatures(e.point, {
                    layers: ['measure-points']
                });

                // Remove the linestring from the group
                // so we can redraw it based on the points collection.
                if (geojson.features.length > 1) geojson.features.pop();



                // If a feature was clicked, remove it from the map.
                if (features.length) {
                    const id = features[0].properties.id;
                    geojson.features = geojson.features.filter(
                        (point) => point.properties.id !== id
                    );
                } else {
                    const point = {
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [e.lngLat.lng, e.lngLat.lat]
                        },
                        'properties': {
                            'id': String(new Date().getTime())
                        }
                    };

                    geojson.features.push(point);
                }

                if (geojson.features.length > 1) {
                    linestring.geometry.coordinates = geojson.features.map(
                        (point) => point.geometry.coordinates
                    );

                    geojson.features.push(linestring);

                    // Populate the distanceContainer with total distance
                    const value = document.createElement('pre');
                    const distance = turf.length(linestring);

                    map_b_size.value = `${distance.toLocaleString()}km`;
                }

                map_b.getSource('geojson').setData(geojson);
            });
        });

        map_bAddModal.on('load', () => {
            map_bAddModal.addSource('geojson', {
                'type': 'geojson',
                'data': geojson
            });

            // Add styles to the map
            map_bAddModal.addLayer({
                id: 'measure-points',
                type: 'circle',
                source: 'geojson',
                paint: {
                    'circle-radius': 5,
                    'circle-color': '#000'
                },
                filter: ['in', '$type', 'Point']
            });

            map_bAddModal.addLayer({
                id: 'measure-lines',
                type: 'line',
                source: 'geojson',
                layout: {
                    'line-cap': 'round',
                    'line-join': 'round'
                },
                paint: {
                    'line-color': '#000',
                    'line-width': 2.5
                },
                filter: ['in', '$type', 'LineString']
            });

            map_bAddModal.on('click', (e) => {
                const features = map_bAddModal.queryRenderedFeatures(e.point, {
                    layers: ['measure-points']
                });

                // Remove the linestring from the group
                // so we can redraw it based on the points collection.
                if (geojson.features.length > 1) geojson.features.pop();

                // If a feature was clicked, remove it from the map.
                if (features.length) {
                    const id = features[0].properties.id;
                    geojson.features = geojson.features.filter(
                        (point) => point.properties.id !== id
                    );
                } else {
                    const point = {
                        'type': 'Feature',
                        'geometry': {
                            'type': 'Point',
                            'coordinates': [e.lngLat.lng, e.lngLat.lat]
                        },
                        'properties': {
                            'id': String(new Date().getTime())
                        }
                    };

                    geojson.features.push(point);
                }

                if (geojson.features.length > 1) {
                    linestring.geometry.coordinates = geojson.features.map(
                        (point) => point.geometry.coordinates
                    );

                    geojson.features.push(linestring);

                    // Populate the distanceContainer with total distance
                    const value = document.createElement('pre');
                    const distance = turf.length(linestring);
                    map_b_sizeAddModal.value = `${distance.toLocaleString()}km`;
                }

                map_bAddModal.getSource('geojson').setData(geojson);
            });
        });

        // for distance
        map_b.on('mousemove', (e) => {
            const features = map_b.queryRenderedFeatures(e.point, {
                layers: ['measure-points']
            });
            // Change the cursor to a pointer when hovering over a point on the map.
            // Otherwise cursor is a crosshair.
            map_b.getCanvas().style.cursor = features.length
                ? 'pointer'
                : 'crosshair';
        });

        // for distance modal
        map_bAddModal.on('mousemove', (e) => {
            const features = map_bAddModal.queryRenderedFeatures(e.point, {
                layers: ['measure-points']
            });
            // Change the cursor to a pointer when hovering over a point on the map.
            // Otherwise cursor is a crosshair.
            map_bAddModal.getCanvas().style.cursor = features.length
                ? 'pointer'
                : 'crosshair';
        });
    }

    map.on('click', add_marker);
    mapAddModal.on('click', add_marker);


    const geocoder = new MapboxGeocoder({
        accessToken: mapboxgl.accessToken, 
        mapboxgl: mapboxgl, 
        marker: false,
        zoom: 18
    });

    map.addControl(geocoder);
    mapAddModal.addControl(geocoder);
});


// exit modal view company
// addguard modal exit btn
let exitModalViewCompany = document.querySelector("#exit-modal-viewcompany");
exitModalViewCompany.addEventListener('click', e => {
    let viewcompanyModal = document.querySelector('.modal-viewcompany');
    viewcompanyModal.style.display = "none";
});







// for company without location
let addhereMain = document.querySelector('#addhere-main');
let addnewMain = document.querySelector('.addnew-main');

// create input fields
addnewMain.addEventListener('click', (e)=>{

    // input name="name"
    let inputName = document.createElement('input');
    inputName.setAttribute('type', 'text');
    inputName.className = "name";
    inputName.setAttribute('placeholder', 'name');
    inputName.setAttribute('onchange', 'computeTotalMain(this)');
    inputName.setAttribute('autocomplete', 'off');

    // input name="price"
    let inputPrice = document.createElement('input');
    inputPrice.setAttribute('type', 'text');
    inputPrice.className = "price";
    inputPrice.setAttribute('placeholder', '00.00');
    inputPrice.setAttribute('autocomplete', 'off');
    
    let createDiv = document.createElement('div');
    createDiv.classList.add('position-container');

    // append in div first
    createDiv.appendChild(inputName);
    createDiv.appendChild(inputPrice);

    // append created elements
    addhereMain.appendChild(createDiv);
    addhereMain.appendChild(createDiv);

    let names = document.querySelectorAll('.name');
    let prices = document.querySelectorAll('.price');

    for(let i = 0; i < names.length; i++){
        if(i > 0){
            names[i].setAttribute('name', `name${i}`);
            prices[i].setAttribute('name', `price${i}`);
        }
    }
    
});


let inputLengthMain = document.querySelector('.length-main');
function computeTotalMain(x){
   if(x.value !== ""){
      if(!x.classList.contains("blocked")){
        let totalMain = parseInt(inputLengthMain.value) + parseInt(1);
        inputLengthMain.value = totalMain;
        x.classList.add('blocked');
      }
   }
}




















// create input fields in view modal
let addhereContainer2 = document.querySelector('#addhere-addmodal');
let addnew2 = document.querySelector('.addnew2');

addnew2.addEventListener('click', (e)=>{

    // input name="name"
    let inputName = document.createElement('input');
    inputName.setAttribute('type', 'text');
    inputName.className = "name-modal";
    inputName.setAttribute('placeholder', 'name');
    inputName.setAttribute('onchange', 'computeTotal2(this)');
    inputName.setAttribute('autocomplete', 'off');

    // input name="price"
    let inputPrice = document.createElement('input');
    inputPrice.setAttribute('type', 'text');
    inputPrice.className = "price-modal";
    inputPrice.setAttribute('placeholder', '00.00');
    inputPrice.setAttribute('autocomplete', 'off');
                
    let createDiv = document.createElement('div');
    createDiv.classList.add('position-container');

    // append in div first
    createDiv.appendChild(inputName);
    createDiv.appendChild(inputPrice);

    // append created elements
    addhereContainer2.appendChild(createDiv);

    let names = document.querySelectorAll('.name-modal');
    let prices = document.querySelectorAll('.price-modal');

    for(let i = 0; i < names.length; i++){
        if(i > 0){
            names[i].setAttribute('name', `name${i}`);
            prices[i].setAttribute('name', `price${i}`);
        }
    }     
});

// inputlength in view modal
let inputLength2 = document.querySelector('.length2');
function computeTotal2(x){
   if(x.value !== ""){
      if(!x.classList.contains("blocked")){
        let total2 = parseInt(inputLength2.value) + parseInt(1);
        inputLength2.value = total2;
        x.classList.add('blocked');
      }
   }
}

// to open add company modal
let addModalShow = document.querySelector('#addmodal-show');
addModalShow.addEventListener('click', (e) => {
    let modalViewCompany = document.querySelector('.modal-viewcompany');
    modalViewCompany.style.display = 'flex';
});


















function getParentElement(e){

    let lengthInput = document.querySelector('#lengthInput');
    lengthInput.value = parseInt(lengthInput.value) - parseInt(1);
    // lengthInput.value = parseInt(lengthInput.value);

    e.parentElement.children[0].value = ''; //position
    e.parentElement.children[1].value = ''; //price

    let myparent = e.parentElement; // div na walang att


    let addhere = e.parentElement.parentElement; // addhere
    let mydiv = addhere.querySelectorAll('div'); // object

    const mydivArray = Object.values(mydiv); // array

    // object
    let filteredDiv = mydivArray.filter( div => { return div != myparent; });
    const filteredDivArray = Object.values(filteredDiv); // array

    console.log(filteredDivArray);
    for(let i = 0; i < filteredDivArray.length; i++){
        filteredDivArray[i].children[0].setAttribute('name', `position${i+1}`);
        filteredDivArray[i].children[1].setAttribute('name', `price${i+1}`);
    }

    myparent.remove();
}

function getParentElementDecrease(element){
    let myParent = element.parentElement;
    let position = myParent.children[0];
    let price = myParent.children[1];
    position.removeAttribute('required');
    price.removeAttribute('required');

    let myNull = null;

    position.value = myNull;
    price.value = myNull;

    let lengthInput = document.querySelector('#lengthInput');
    let lengthInputOriginal = document.querySelector('#lengthInputOriginal');
    lengthInput.value -= 1;
    lengthInputOriginal.value -= 1;

    myParent.remove();
}