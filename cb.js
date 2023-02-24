$(document).ready( function () {
  $('#trans_table').DataTable({
    searching: false,
    ordering:  false
});
} );

function editTrans(id){
  $('#type').val('edit');
  $('#button').val('Edit Transaction');
  $('#trans_id').val(id);
  $('#transtitle').html('Edit Transaction')
  $.get("handler.php?type=gettransaction&id="+id, function(data, status){
    const trans = JSON.parse(data);
    $('#amount').val(Math.abs(trans.amount));
    $('#date').val(trans.trans_date);
    $('#checknumber').val(trans.checknumber);
    $('#description').val(trans.description);
    if (trans.amount < 0){
        $('#trans_type').val('withdrawal');
    }   else    {
        $('#trans_type').val('deposit');
    }
  });
  $('#delete_button').show();
  $('#transModal').modal('show');
  setWidth('trans_header');
  setWidth('buttongrp');
}

function editSched(id){
  $('#type').val('editsched');
  $('#button').val('Edit Schedule');
  $('#trans_id').val(id);
  $('#transtitle').html('Edit Schedule')
  $.get("handler.php?type=getschedule&id="+id, function(data, status){
    const trans = JSON.parse(data);
    $('#amount').val(Math.abs(trans.amount));
    $('#date').val(trans.dayofmonth);
    $('#description_sched').val(trans.description);
    $('#active').val(trans.active);
  });
  $('#delete_button').show();
  $('#transModal').modal('show');
  setWidth('trans_header');
  setWidth('buttongrp');
}

function delTrans(){
  id = $('#trans_id').val();
  console.log('delete '+id);
  $.get("handler.php?type=deltrans&id="+$('#trans_id').val(), function(data, status){
    console.log(status);
  }).done(function(){
    window.location.replace(window.location.href);
  });
}

function delSched(){
  id = $('#trans_id').val();
  console.log('delete '+id);
  $.get("handler.php?type=delsched&id="+$('#trans_id').val(), function(data, status){
    console.log(status);
  });
  window.location.replace(window.location.href);
}
function newTrans(){
  $('#type').val('new');
  $('#button').val('Add Transaction');
  $('#transtitle').html('Add Transaction')
  $('#trans_id').val('');
  $('#amount').val('');
  $('#date').val(new Date().toISOString().slice(0, 10))
  $('#description').val('');
  $('#trans_type').val('withdrawal');
  $('#delete_button').hide();
  $('#transModal').modal('show');
  setWidth('trans_header');
  setWidth('buttongrp');
}

function newSched(){
  $('#type').val('newsched');
  $('#button').val('Add Schedule');
  $('#transtitle').html('Add Schedule')
  $('#trans_id').val('');
  $('#amount').val('');
  $('#date').val('28');
  $('#active').val('1')
  $('#description_sched').val('');
  $('#delete_button').hide();
  $('#transModal').modal('show');
  setWidth('trans_header');
  setWidth('buttongrp');
}

function exportCSV(){
  document.getElementById('search_form').action = "search.php";
  document.getElementById('search_form').submit();
  document.getElementById('search_form').action = "";
}

function setWidth(cname){
  var wide = 0;
  var els = document.getElementsByClassName(cname);
  for(var i = 0; i < els.length; i++)
  {
    wide = els[i].clientWidth > wide ? els[i].clientWidth : wide;
  }
  for(var i = 0; i < els.length; i++)
  {
    els[i].style.width = wide+"px";
  }
}

function resetForm(cname){
  var els = document.getElementsByClassName(cname);
  for(var i = 0; i < els.length; i++)
  {
    els[i].value = '';
  }
}