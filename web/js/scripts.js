$(document).ready(function() {
  $('.task .exp-icon').on('click', function() {
    var el = $(this).parent('.task');

    if (el.hasClass('active')) {
      el.removeClass('active');
      el.children('.hidden').slideUp();
    }
    else {
      $('.task').removeClass('active');
      $('.task').children('.hidden').slideUp();
      el.addClass('active');
      el.children('.hidden').slideDown();
    }
  });

  $('.task').not('.active').on('mouseover', function() {
    $(this).children('.task-overlay').show();
  });

  $('.task').not('.active').on('mouseout', function() {
    $(this).children('.task-overlay').hide();
  });

})
