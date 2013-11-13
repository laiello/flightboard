(function($){
	parseFlights();
	setInterval(parseFlights, 30000);
	
	function ajaxSetup() {
		$.ajaxSetup({
			cache: false
		});
	}
	
	function parseFlights() {
		var url = "flightdata.php";
	
		ajaxSetup();
	
		$.ajax({
			type: "GET",
			dataType: 'json',
			url: url,
			success: function(response) {
				var table = $('#flights > tbody:last');
				var flights = response;

				for (var i = 0; i < flights.length; i += 1) {
					var flight = flights[i];
					console.log(flight);
					var row = $('#flight' + flight.carrier + flight.flightnumber, table);
				
					if (row && row.length > 0) {
						$('td.status', row).html(flight.status);						
					} else {
						var rowClass = ($('#flights > tbody:last tr:last').hasClass('even')) ? 'odd' : 'even';
						var str = '<tr id="flight' + flight.carrier + flight.flightnumber + '" class="' + rowClass + '">';

						str += createCell('carrier', flight.carrier);
						str += createCell('airport', flight.airport);
						str += createCell('flightnumber', flight.carrier + ' ' + flight.flightnumber);
						str += createCell('scheduled', flight.est_time);
						if(flight.act_time == "00:00")
						{
							act_time = flight.est_time;
						}else{
							act_time = flight.act_time;
						}
						str += createCell('actual', act_time);
						str += createCell('status', flight.status);

						str += '</tr>';								
						table.append(str);
					}
				}
			}
		});
	}
	
	
	function createCell(name, data) {
		if (name == 'status') {
			if (data == 'Departed') {
				name += ' departed';
			}
		}
	
		return '<td class="' + name + '">' + data + '</td>';
	}



})(window.jQuery);

window.log = function(){
  log.history = log.history || []; 
  log.history.push(arguments);
  if(this.console){
    console.log( Array.prototype.slice.call(arguments) );
  }
};

(function(doc){
  var write = doc.write;
  doc.write = function(q){ 
    log('document.write(): ',arguments); 
    if (/docwriteregexwhitelist/.test(q)) write.apply(doc,arguments);  
  };
})(document);


