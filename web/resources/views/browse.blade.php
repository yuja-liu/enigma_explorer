@extends('template')

@section('title', 'Browse')

@section('content')
<!-- <div class="row my-4 d-none">
  <div class="col-lg-12">
    <h1>By Orders</h1>
    <hr />
  </div>
  <div class="col-lg-12 text-left" id="orderData"></div>
</div>
<div class="row my-4 d-none">
  <div class="col-lg-12">
    <h1>By Genera</h1>
    <hr />
  </div>
  <div class="col-lg-12 text-left" id="genusData"></div>
  <div class="col-lg-12"><hr /></div>
</div> -->
<div class="row my-2">
  <div class="col-sm-12">
    <button class="btn btn-success disabled" role="submit" id="downloadBtn">Download</button>
  </div>
</div>
<div class="row my-4">
  <div class="col-sm-12">
    <table class="table" id="taxaDataView">
      <thead>
        <th>#</th>
        <th> </th>
        <th>Taxon</th>
        <th>#</th>
        <th>Select</th>
      </thead>
    </table>
  </div>
</div>
<script>
  // fetch orders and genus data
  /*function fetchList(dataView, url, startC, endC) {
    $.ajax({
      url: url,
      success: function(data) {
        // color of tags will randomly vary within a range
        for (let key in data) {
          factor = Math.random();
          mcolor = [];
          for (let i=0; i<3; i++) {
            mcolor.push(startC[i] + (endC[i]-startC[i])*factor);
          }
          var itemString = `
            <div style="background-color:${'rgb('+mcolor.join(',')+')'}" class="myitems btn btn-light inline-block my-2 mx-4">
              <a class="text-white" href="/search?keyword=${key}">${key}</a>
              <span class="badge badge-light">${data[key]}</span>
            </div>`;
          $(dataView).append(itemString);
        }
        // remove marginal margins
        $(dataView + '>div:first-child').css('margin-left', '0px');
        $(dataView + '>div:last-child').css('margin-right', '0px');
      },
      error: function() {
        var errorString = `
          <p class="bg-danger">Unexpected server error. Please try again.</p>`;
        $(dataView).append(errorString);
      }
    });
  }*/

  function fetchTaxa() {
    $.ajax({
      url: "/api/v1/isolates/taxa",
      success: function(data) {
        for (let order in data) {
          // Build order string. The group of genus will be in tbody named $order
          let orderString = `
            <tbody id="head_${order}">
              <tr class="orderRow" id="${order}">
              <!-- order level text will be bigger -->
                <!-- Below pr-4 compensates the pl-4 indent in genus level to avoid jitter -->
                <th class="pr-4"><a class="text-dark" data-toggle="collapse" href="#group_${order}"><span class="fa fa-chevron-down"></span></a></th>
                <td class="pr-4"><a class="text-dark" href="/search?keyword=${order}">${order}</a></td>
                <td><span class="text-secondary">order</span></td>
                <td>
                  <span class="badge badge-info">${data[order].nGenera}</span>
                  <span class="badge badge-success">${data[order].tSpecies}</span>
                  <!-- Blue for #genus and green for #species -->
                </td>
                <td>
                  <button class="btn btn-sm btn-light checkBtn" id="cb_${order}" role="checkbox">&nbsp&nbsp&nbsp</button>
                </td>
              </tr>
            </tbody>`;
          $('#taxaDataView').append(orderString);
          // Build genus string by ele.genera
          // all genus are grouped by a tbody named by the order
          let genusGroupString = `
            <tbody class="collapse" id="group_${order}"></tbody>`;
          $('#taxaDataView').append(genusGroupString);
          // Iterate all genus
          let cGenera = data[order].genera;
          for (let genus in cGenera) {
            let genusString = `
              <tr class="bg-light genusRow" id="${genus}">
                <th class="pl-4"><a class="text-dark" data-toggle="collapse" href=".group_${genus}"><span class="fa fa-chevron-down"></span></a></th>
                <td class="pl-4"><em><a class="text-dark" href="/search?keyword=${genus}">${genus}</a></em></td>
                <td><span class="text-secondary">genus</span></td>
                <td><span class="badge badge-success">${cGenera[genus]}</span></td>
                <td>
                  <button class="btn btn-sm btn-light checkBtn" id="cb_${genus}" role="checkbox">&nbsp&nbsp&nbsp</button>
                </td>
              </tr>`;
            $('#group_' + order).append(genusString);
          }

          // Hook to load species
          // Notice the lazy loading here: only when order is expanded the species will be loaded
          $('#head_'+order+' th').click(function() {
            for (let genus in cGenera) {
              fetchSpecies(genus);
            }
            $(this).unbind();
          });
        }
      },
      error: function() {
        var errorString = `
          <p class="bg-danger">Unexpected server error. Please try again.</p>`;
        $('#taxaDataView').append(errorString);
      }
    });
  }

  function fetchSpecies(genus) {
    // Fetch isolates from given genus
    // Assume a <tr> named by the genus already exists
    if ($('#'+genus).length > 0) {
      $.ajax({
        url: "/api/v1/isolates/genus/" + genus,
        success: function(data) {
          // Iterate all isolates, reversed
          for (let i=data.length-1; i>=0; i--) {
            // Build species string, should have class of genus
            let species = data[i];
            let speciesString = `
              <tr class="collapse speciesRow group_${genus}" id="${species.id}">
                <th></th>
                <td class="pl-4"><a class="text-dark" href="/isolates/id/${species.id}">${species.isolate_id}</td>
                <td></td>
                <td><em>${species.closest_relative}</em></td>
                <td>
                  <button class="btn btn-sm btn-light checkBtn" id="cb_${species.id}" role="checkbox">&nbsp&nbsp&nbsp</button>
                </td>
              </tr>`;
            // Notice the genus level has id of $genus
            $('#'+genus).after(speciesString);
          }
          // If genus is checked, propogate
          let isSelected = $('#cb_'+genus).hasClass('btn-primary');
          if (isSelected) {
            // at this time species ele are rendered
            $('.group_'+genus+' .checkBtn').trigger('click');
          }
        },
        error: function() {
          var errorString = `
            <p class="bg-danger">Unexpected server error. Please try again.</p>`;
          $('#'+genus).append(errorString);
        }
      });
    } else {
      // Force log error if genus does not exist
      console.log('<tr> ' + genus + 'does not exist. Invalid append of isolates');
    }
  }

  // Hierarchically retrieve all selected iso ids
  function jsonSelection() {
    // Iterate on all species level ele
    let ret = {};
    $('.speciesRow .checkBtn.btn-primary').each(function() {
      let isoid = $(this).attr('id').slice(3);
      let speciesRow = $(this).parents('tr.speciesRow');
      let genus = $(speciesRow).attr('class').match(/group_\w+/)[0].slice(6);    //2nd class is group_$genus
      let order = $(speciesRow).parent().attr('id').slice(6);    // id is group_$order
      // push into ret
      if (order in ret) {
        if (genus in ret[order]) {
          ret[order][genus].push(isoid);
        } else {
          ret[order][genus] = [ isoid ];
        }
      } else {
        ret[order] = {};
        ret[order][genus] = [ isoid ];
      }
    });
    // yeild taxa json
    return ret;
  }

  $(document).ready(function() {
    fetchTaxa();
    //fetchList('#orderData', '/api/v1/isolates/orders', [85, 239, 196], [0, 184, 148]);
    //fetchList('#genusData', '/api/v1/isolates/genera', [116, 185, 255], [9, 132, 227]);

    // Proceed to download
    $('#downloadBtn').click(function() {
      let taxaJson = jsonSelection();
      // redirect to download
      $.ajax({
        url: '/api/v1/isolates/taxa/rrna',
        type: 'POST',
        data: taxaJson,
        success: function(data) {
          // open download path
          window.open(data.path, '_blank');
        },
        error: function() {
          var errorString = `
            <p class="bg-danger">Unexpected error in downloading. Please try again.</p>`;
          $('#taxaDataView').before(errorString);    // Show msg before main table
        }
      });
    });

    // Checkbox events Note checkBtns are not loaded at this time
    $('#taxaDataView').on('click', '.checkBtn', function() {
      let isSelected = $(this).hasClass('btn-primary');
      // Swith between grey and blue with a tick
      let name = $(this).attr('id').slice(3);
      if (!isSelected) {
        // Activate
        $('#cb_'+name).trigger('activate');
      } else {
        // Deactivate
        $('#cb_'+name).trigger('deactivate');
      }
    });

    // Custom checkbox activate/deactivate event
    $('#taxaDataView').on('activate', '.checkBtn', function() {
      $(this).html(`
        <span class="fa fa-check"></span>`);
      $(this).removeClass('btn-light');    // Will not panic if the class inexist
      $(this).addClass('btn-primary');
      // Trigger class change to activate/deactivate the download btn
      $(this).trigger('classChanged');
    });
    $('#taxaDataView').on('deactivate', '.checkBtn', function() {
      $(this).html('&nbsp&nbsp&nbsp');
      $(this).removeClass('btn-primary');    // Will not panic if the class inexist
      $(this).addClass('btn-light');
      // Trigger class change to activate/deactivate the download btn
      $(this).trigger('classChanged');
    });
    // species level specific deactivate
    $('#taxaDataView').on('deactivate', '.speciesRow .checkBtn', function() {
      // up propogate deactivation
      let classes = $(this).parents('tr').attr('class');
      let genus = classes.match(/group_\w+/)[0].slice(6);    // like group_$genus. always be 2nd class
      $("#cb_"+genus).trigger('deactivate');
    });
    $('#taxaDataView').on('deactivate', '.genusRow .checkBtn', function() {
      let genusGroup = $(this).parents('tbody');    // indirect parent is tbody
      let order = $(genusGroup).attr('id').slice(6);    // like group_$order
      $('#cb_'+order).trigger('deactivate');
    });

    $('#taxaDataView').on('classChanged', '.checkBtn', function() {
      // Count the number of activated cbs. Note alternations are done
      let nSelect = $('.checkBtn.btn-primary').length;
      if (nSelect == 0) {
        // Empty selection
        $('#downloadBtn').addClass('disabled');
      } else {
        $('#downloadBtn').removeClass('disabled');
      }
    });

    // If order level check button
    $('#taxaDataView').on('click', '.orderRow .checkBtn', function() {
      let order = $(this).attr('id').slice(3);    // Named as #cd-$order
      // propogate the click
      let isSelect = $(this).hasClass('btn-primary');
      if (isSelect) {
        $('#group_'+order+' .checkBtn').trigger('activate');
      } else {
        $('#group_'+order+' .checkBtn').trigger('deactivate');
      }
      // Force to expand. Because detailed species have to be loaded
      // If one only triggers th, then species will be loaded but not to expand
      $(this).parents('.orderRow').children('th').trigger('click');
    });

    // If genus level check button
    $('#taxaDataView').on('click', '.genusRow .checkBtn', function() {
      let genus = $(this).attr('id').slice(3);
      let isSelect = $(this).hasClass('btn-primary');
      if(isSelect) {
        $('.group_'+genus+' .checkBtn').trigger('activate');
      } else {
        $('.group_'+genus+' .checkBtn').trigger('deactivate');
      }
    });
  });
</script>
@endsection