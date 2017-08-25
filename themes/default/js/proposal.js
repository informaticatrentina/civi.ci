var panelcontainer = '';
var neutral = new Array(1, 3, 7);
var agree = new Array(5, 6, 2);
var disagree = new Array(8, 9, 4);
var triangle = '';
var targetLang = '';
var authorSlug = '';
document.title = $('.pull-left > h1').text() + '| ' + title;
$(document).ready(function() {
  $('.openModal').click(function() { 
    if ($('.modal-menu-bar').children("li").first().attr('class') != 'active') {
      $('.modal-menu-bar').children("li").first().siblings().removeClass('active');
      $('.modal-menu-bar').children("li").first().addClass('active');
      $('#proposal').addClass('active');
      $('#opinion').removeClass('active');
      $('#links').removeClass('active');
    }
    $('#opinion-msg').html(' ');
    $('.opiniontext').siblings('.words').text(ocharLimit);
    $('.opiniontext').val('');
    $('.tmodal-launcher').show();
    $('.triangle-text').show();
    $('.sl').css({'stroke': '', 'stroke-width': 0});
    $('.triangle-position-text').html('');
    $('#opinion-box-text').val('');
    $('.tmodal-content').hide();
    var appliedClass = $('.proposal').attr('class').split(' ');
    for (i = 1; i < appliedClass.length; i++) {
      if (appliedClass[i].substr(0, 4) === 'pid-') {
        $('.proposal').removeClass(appliedClass[i]);
        $('.link-page-login').removeClass(appliedClass[i]);
        $('.opinion-page-login').removeClass(appliedClass[i]);
        break;
      }
    }
    $('#modalBox').children('.modal-dialog').children('.modal-content').children('.modal-body').children('.content').html('');
    $(this).parents('.singleProposal').addClass('active-parent');
    $(this).parents('.singleProposal').siblings().removeClass('active-parent');
    var aside = $('#opinion').children('.opinion-text').children('aside');
    $('#opinion').children('.opinionbox').html('');
    $('#links').children('.panelcontainer').children('.panelinner').children('.current-links').html('');
    $('#opinion').children('.loading-image').show(); 
    $('#links').children('.panelcontainer').children('.panelinner').children('.loading-image').show();
    var title = $(this).children('.proposalheader').text();
    $('.submit-link-msg').text('');
    var img = $(this).children('.byline').children('img').attr('src');
    var authorName = $(this).siblings('.authName').val();
    authorSlug = $(this).siblings('.author-slug').val();
    var headerHtml =  '<div class="col-lg-2 col-md-2 col-sm-2 col-xs-3">\n\
                         <img src="'+ img +'" class="img-responsive img-circle"> \n\
                       </div>\n\
                       <div class="col-lg-10 col-md-10 col-sm-10 col-xs-9">\n\
                         <h3 class="modal-title" id="myModalLabel">'+ title +'</h3><a href="' + userProfileUrl + authorSlug +'">da '+ authorName +'</a> \n\
                       </div>'
    $('#modalBox').children('.modal-dialog').children('.modal-content').children('.modal-header').children('.row').html(headerHtml);
    $('#modalBox').children('.modal-body').children('.content').append(propDiv);
    var id = $(this).siblings('.propId').val();    
    var profileUrl = $(this).siblings('.profileUrl').val();
    $('#id').val(id);
    $('.proposal-id').val(id);
    var description = $(this).siblings('.summary').val();
    var summary = $(this).children('summary').html();    
    $('#proposal').html('');
    var propDiv = '<article><div class="clearfix"></div><p><summary>' + summary + '</summary></p><div class="clearfix"></div><p><description>' + description + '</description></p></article>';
    var area = $(this);
    propDiv += prepareProposalVideoImageHtml(area);
    $('#proposal').append(propDiv);
    $.ajax({
      type: 'POST',
      data: {pid: id, action: 'getData'},
      url: document.URL,
      success: function(resp) {
        $('#opinion').children('.loading-image').hide();
        $('#links').children('.panelcontainer').children('.panelinner').children('.loading-image').hide();
        var opinionBox = '';
        var linkBox = '';
        $('.link-page-login').addClass('pid-' + id);
        $('.opinion-page-login').addClass('pid-' + id);
        $('.proposal').addClass('pid-' + id); 
        if (typeof(resp.msg.hasUserSubmitted) == 'object') { 
          $('.post').html(Yii.t('js','Update Opinion'));
          $('.id').val(resp.msg.hasUserSubmitted.id);
          $('.upd').val(1);          
          textarea = resp.msg.hasUserSubmitted.content.description;
          textarea = decodeHtml(textarea); 
          $('#opinion').children('.row').children('div').find('form').find('.opinion-textbox').removeAttr('disabled');
          $('#opiniontext').val(textarea);
          $('#opinion-box-text').val(textarea);
          for (key in resp.msg.hasUserSubmitted.tags) {
            if (resp.msg.hasUserSubmitted.tags[key].slug == 'triangle') {
              if (resp.msg.hasUserSubmitted.tags[key].weight > 0) {
                $('#position-msg').text(Yii.t('js','Thank you for giving us your position on the proposal.')).addClass('alert-success');
              }
              $('.index').val(resp.msg.hasUserSubmitted.tags[key].weight);
              $('.tmodal-launcher').each(function() {
                if ($(this).children('polygon').attr('index') == resp.msg.hasUserSubmitted.tags[key].weight) {
                  $(this).children('polygon').css({'stroke': 'black', 'stroke-width': 1});
                  $('.triangle-position-text').html($(this).children('polygon').attr('msg'));
                }
              });
              var indexs = resp.msg.hasUserSubmitted.tags[key].weight;
              $('.prevmsg').val(triangles[indexs].msg);
              $('.previndex').val(resp.msg.hasUserSubmitted.tags[key].weight);
            }
            if (resp.msg.hasUserSubmitted.tags[key].slug == 'understanding') {
              $('.understanding').val(resp.msg.hasUserSubmitted.tags[key].weight);
            }
            if (resp.msg.hasUserSubmitted.tags[key].slug == 'comprehension') {
              $('.comprehension').val(resp.msg.hasUserSubmitted.tags[key].weight);
            }
          }
          if ((ocharLimit - $('.opiniontext').val().length) < 0) {
            $('.opiniontext').siblings('.words').text(0);
          } else {
            $('.opiniontext').siblings('.words').text(ocharLimit - $('.opiniontext').val().length);
          }
        } else {
          $('.post').html(Yii.t('js','Submit Opinion'));
          $('.upd').val(0);
          $('.id').val('');
          $('.index').val('');
          $('.understanding').val('');
          $('.comprehension').val('');
          $('.previndex').val('');
        }
        if (resp.msg.opinion == 0) {
          opinionBox += '<hr><div class="row opinion none">"' + Yii.t('js', 'No reviews yet') + '"</div>';
          $('#opinion').children('.opinionbox').append(opinionBox);
          opinionBox = '';
        } else {
          flagUrl = '';
          for (var key in resp.msg.opinion) {
            if (resp.msg.opinion[key].content.description != '') {
              if (resp.msg.opinion[key].nextLang == '') {
                var imgFlag = '';
              } else {
                var imgFlag = '<img class="imgflag" src="' + flagUrl + resp.msg.opinion[key].nextLang + '.png "/>';
              }
              opinionBox += '<hr><div class="row ' + resp.msg.opinion[key].class + ' by-' + resp.msg.opinion[key].author.slug + '">\n\
                              <div class="col-lg-2 col-md-2 col-sm-2 col-xs-3">\n\
                                <img class="img-responsive img-circle" src="' + imageUrl + resp.msg.opinion[key].author.slug + '/' + imageSize + '"/>\n\
                              </div>\n\
                              <div class="col-lg-10 col-md-10 col-sm-10 col-xs-9">\n\
                                <p><strong><a href=' + profileUrl + resp.msg.opinion[key].author.slug + '>' + resp.msg.opinion[key].author.name + '</a></strong>';
              if (resp.msg.opinion[key].content.description != '') {
                opinionBox += ': <description>' + resp.msg.opinion[key].content.description + '</description>';
              }
              var opinionId = resp.msg.opinion[key].id;
              var opinionInfo = resp.msg.answer_on_opinion[opinionId];
              if (typeof opinionInfo != 'undefined') {
               for(var i = 0; i < opinionInfo.length; i++) {
                  opinionBox += '<hr><p class="small">' + opinionInfo[i]['content']['description'] +
                                ' - <strong>' + opinionInfo[i]['author']['name'] + '</strong></p>';
                }
              }
              if (isCurator == true || authorSlug == loginUserslug) {
                opinionBox += prepareAnswerHtml(opinionId);
              }
              opinionBox += '</div></div>';
              $('#opinion').children('.opinionbox').append(opinionBox);
              opinionBox = '';
            }
          }
          initializePopover();
        }
        if (resp.msg.link == 0) {
          $('.link-count').val(0);
          linkBox += '<hr><h5>' + Yii.t('js', 'It has not been added to any link, you can be the first.') + '</h5>';
          $('#links').children('.panelcontainer').children('.panelinner').children('.current-links').append(linkBox);
          linkBox = '';
        } else {
          var linkCount = 0;
          for (var key in resp.msg.link) {
            linkCount++;
            linkBox += '<hr><h5><a href="' + resp.msg.link[key].content.summary + '" target="_blank">' + resp.msg.link[key].content.summary + '</a></h5>' + resp.msg.link[key].content.description;
            $('#links').children('.panelcontainer').children('.panelinner').children('.current-links').append(linkBox);
            linkBox = '';
          }
          $('.link-count').val(linkCount);
        }
      },
      error: function() {
        $('#links').children('.panelcontainer').children('.panelinner').children('.loading-image').hide();
        alert('An error occured!');
      }
    });
    $('#metodo').attr('href', profileUrl + authorSlug + '/method');
    $('#modalBox').modal('show');
  });
  $(document).click(function(event) {
    if ($('#modalBox').is(':visible')) {
      if (!$(event.target).closest("#modalBox").length) {
        removeActiveClass();
        closePanelContainer();
      }
    }
  });
  $('.close').click(function(e) {
    e.stopImmediatePropagation();
    removeActiveClass();
    $('#modalBox').modal('hide');
    $('#formModal').modal('hide');
  });
  var $container = $('.allproposals');
  if (proposalColumns == 3) {
    $container.imagesLoaded(function() {
      $container.masonry({
        itemSelector: '.post-box',
        columnWidth: '.post-box',
        transitionDuration: 0
      });
    });
  }
  $('.opinionmap').on('mouseenter click', function(event) {
    event.preventDefault();
    $(this).popover({title: Yii.t('js', 'Current Opinion'), trigger: 'hover', html: 'true', content: $(this).siblings('.pop').html(), placement: 'top'}).popover('show');
  });
  if ($('.slidess').length > 0) {
    $('.slidess').on('click', function() {
      $(this).parents('.modal').modal('hide');
      $('#myModalx').modal('show');
    });
  }
  var index = '';
  var understanding = '';
  var comprehension = '';
  var msg = '';
  $('.documents').click(function(e) {
    if ($(this).siblings('aside').children('.panelcontainer2').is(":visible")) {
      $(this).siblings('aside').children('.panelcontainer2').slideUp(400);
    }
    e.preventDefault();
    var panelcontainer = $(this).siblings('.panelcontainer');
    if (panelcontainer.is(":visible")) {
      $(this).children('.document').text(Yii.t('js','See the enclosed link'));
      panelcontainer.slideUp(400);
    } else {
      $(this).children('.document').text(Yii.t('js','Hide the enclosed link'));
      panelcontainer.slideDown(400);
    }
  });
  $(".tmodal-launcher").click(function() {
    index = $(this).find('.sl').attr('index');
    understanding = $(this).find('.sl').attr('understanding');
    comprehension = $(this).find('.sl').attr('comprehension');
    msg = $(this).find('.sl').attr('msg');
    msg = '<p>' + msg + '</p>';
    $('.index').val(index);
    $('.understanding').val(understanding);
    $('.comprehension').val(comprehension);
    $('#opinion-msg').html('');
    $('#position-msg').text(Yii.t('js','Thank you for giving us your position on the proposal.')).addClass('alert-success');
    $('.triangle-position-text').html('<hr/>' + msg);
    $('.sl').css({'stroke':'', 'stroke-width':0});
    $(this).find('.sl').css({'stroke':'black', 'stroke-width':1});
    $(this).parents('.triangleBox').parents('.row').siblings('.prevmsg').val(msg);
    saveTrianglePostion(index, understanding, comprehension, msg);
    return false;
  });
  $(".tmodal-background, .tmodal-close").click(function(e) {
    e.preventDefault();
    $(this).parent('.tmodal-content').hide();
  });
  $(".panel2").click(
          function(event) {
            event.preventDefault();
            $(this).parent().siblings('.triangleBox').siblings('.tmodal-content').hide();
            if (panelcontainer.is(":visible")) {
              panelcontainer.slideUp(400);
            } else {
              panelcontainer.slideDown(400);
            }
          }
  );
  $(".slides").click(
          function(event) {
            if ($(this).parents('aside').siblings('.panelcontainer').is(":visible")) {
              $(this).parents('aside').siblings('.panelcontainer').slideUp(400);
            }
            panelcontainer = $(this).parents('.post-opinion').siblings('.panelcontainer2');
            event.preventDefault();
            if (panelcontainer.is(":visible")) {
              panelcontainer.slideUp(400);
            } else {
              text = panelcontainer.find('.upd').val();
              setTimeout(function() {

              }, 500);
              panelcontainer.slideDown(400);
              if (text == 1) {
                var ud = panelcontainer.find('.understanding').val();
                var msg = '<p>' + panelcontainer.find('.prevmsg').val() + '</p>';
                var cp = panelcontainer.find('.comprehension').val();
                panelcontainer.find('.tmodal-content').show();
                panelcontainer.find('.tmodal-background').show();
                panelcontainer.find('.message').html(msg);
                $('.opiniontext').siblings('.words').text(ocharLimit - $('.opiniontext').val().length);
              } else {
                $('.opiniontext').siblings('.words').text(ocharLimit);
                $('.opiniontext').val('');
                $('.tmodal-launcher').show();
                $('.triangle-text').show();
                $('.tmodal-content').hide();
                $('.tmodal-background').hide();
              }
            }
            return false;
          }
  );
  /**
   * 
   * 
   *
   **/
  $("#panel1").click(
          function(event) {
            event.preventDefault();
            if (panelcontainer.is(":visible")) {
              panelcontainer.slideUp(400);
            } else {
              panelcontainer.slideDown(400);
            }
          }
  );
  $('.post').click(function(e) {
    var button = $(this);
    e.preventDefault();
    var data = $(this).parents('.opinionform').serialize();
    var textarea = $('#opiniontext').val();
    var index = $('.index').val();
    if ($.trim(textarea) === '') {
      $('#opinion-msg').html(Yii.t('js', 'Opinion field can not be blank')).addClass('alert-danger');
      return false;
    }
    if (index == '') {
      $('#opinion-msg').html(Yii.t('js', 'You have to choose a position on the triangle!')).addClass('alert-danger');
      return false;
    }
    $('#opinion-msg').html(' ');
    $('.post').hide();
    $('#opinion-save-image').show();    
    $.ajax({
      type: 'POST',
      data: data,
      url: document.URL,
      success: function(resp) {
        var pop = '';       
        $('#opinion-msg').removeClass();
        $('.msg').removeAttr("style");
        $('.msg').html(Yii.t('js', 'Click on one of the triangles below to register your opinion on this proposal.'));
        $('#opinion-save-image').hide();
        $('.post').show();
        if (resp.status) {
          $('#opinion-msg').html(Yii.t('js', 'Opinion has been saved successfully. You can edit your opinion at any time.')).addClass('alert-success');
          $('.post').html(Yii.t('js', 'Update Opinion'));
        } else {
          $('#opinion-msg').html(Yii.t('js', 'Please enter proper text in opinion')).addClass('alert-danger');
          return false;
        }
        textarea = resp.opinion_text;
        $('#opinion-box-text').val(textarea);
        var text = $(button).parent('fieldset').siblings('.upd').val();
        if (text == 0) {
          $(button).parent('fieldset').siblings('.id').val(resp.opinion_id);
          $(button).parent('fieldset').siblings('.upd').val(1);
          $(button).parent('fieldset').siblings('.previndex').val(index);
        }
        setOpinionComment();
        setOpinionCountAndHeatMap(resp.msg);
      },
      error: function() {
        $('#opinion-save-image').hide();
        $('.post').show();
        alert('an error occured');
      }
    });
  });
  $('.type').change(function() {
    var type = $(this).val();
    $(this).parents().siblings('.opinionbox').children('.row').hide();
    $(this).parents().siblings('.opinionbox').children('.row').prev().hide();
    switch (type) {
      case 'agree':
        $(this).parents().siblings('.opinionbox').children('.agree').show();
        $(this).parents().siblings('.opinionbox').children('.agree').prev().show();
        break;
      case 'disagree':
        $(this).parents().siblings('.opinionbox').children('.disagree').show();
        $(this).parents().siblings('.opinionbox').children('.disagree').prev().show();
        break;
      case 'neutral':
        $(this).parents().siblings('.opinionbox').children('.neutral').show();
        $(this).parents().siblings('.opinionbox').children('.neutral').prev().show();
        break;
      default:
        $(this).parents().siblings('.opinionbox').children('.row').show();
        $(this).parents().siblings('.opinionbox').children('.row').prev().show();
        break;
    }
  });
  $('.proposalOpinion').click(function() {
    var url = $(this).attr('href');
    window.location.href = url;
  });
  $('.proposalLinks').click(function() {
    var url = $(this).attr('href');
    window.location.href = url;
  });
  $('.submit-link').click(function(e) {
    e.preventDefault();
    var linkUrl = $.trim($(this).siblings('.form-group').find('.link-url').val());
    var description = $.trim($(this).siblings('.form-group').find('.link-description').val());
    var linkAction = $(this).siblings('.link-action').val();

    var proposalId = $(this).siblings('.proposal-id').val();
    var linkCount = $(this).siblings('.link-count').val();
    var urlRegex = /^(https?:\/\/)?[a-z0-9]+([\-\.]{1}[a-z0-9]+)*\.[a-z]{2,5}(:[0-9]{1,5})?(\/.*)?$/;
    if (linkUrl == '') {
      $(this).siblings('.submit-link-msg').addClass('alert-danger');
      $(this).siblings('.submit-link-msg').html(Yii.t('js', 'Link can not be empty'));
      return false;
    } else if (!urlRegex.test(linkUrl)) {
      $(this).siblings('.submit-link-msg').addClass('alert-danger');
      $(this).siblings('.submit-link-msg').html(Yii.t('js', 'Please enter valid link url'));
      return false;
    } else {
      $(this).siblings('.submit-link-msg').removeClass('alert-danger');
      $(this).siblings('.submit-link-msg').html(' ');
    }
    var area = $(this);
    linkUrl = addHttpInUrl(linkUrl);
    $(this).hide();
    $(this).siblings('.loading-image').show();
    $.ajax({
      type: 'POST',
      dataType: 'json',
      url: document.URL,
      data: {
        action: linkAction,
        id: proposalId,
        link: linkUrl,
        description: description,
      },
      success: function(resp) {
        $(area).siblings('.loading-image').hide();
        $(area).show();
        var linkCount = $(area).siblings('.link-count').val();
        $('.link-count').val(1);
        if (resp.status) {
          $(area).siblings('.submit-link-msg').removeClass('alert-danger');
          $(area).siblings('.submit-link-msg').addClass('alert-success');
          $(area).siblings('.submit-link-msg').html(resp.msg.msg);
          $(area).siblings('.form-group').find('.link-description').val(' ');
          $(area).siblings('.form-group').find('.link-url').val(' ');
          if (resp.data.link_url != '') {
            linkUrl = resp.data.link_url;
          }
          if (resp.data.description != '') {
            description = resp.data.description;
          }
          var html = '';
          var choplinkUrl = '';
          choplinkUrl = chopUrl(linkUrl);
          html = '<hr><h5><a href="' + linkUrl + '" target="_blank">' + choplinkUrl + '</a></h5>';
          html += preserveNewLine(description);
          $('.active-parent').children('.proposal').children('footer').children('.documents').children('count').text(resp.msg.count);
          if (linkCount == 0) {
            $(area).parents('.link-box').siblings('.current-links').html(html);
          } else {
            $(area).parents('.link-box').siblings('.current-links').prepend(html);
          }
          if (typeof(area.parents().children('.active').attr('class')) == 'undefined') {
            area.parents().siblings().children('.active').children('footer').children('.documents').children('count').text(resp.msg.count);
          } else {
            area.parents().children('.active').children('footer').children('.documents').children('count').text(resp.msg.count);
          }
        } else {
          $(area).siblings('.submit-link-msg').removeClass('alert-success');
          $(area).siblings('.submit-link-msg').addClass('alert-danger');
          $(area).siblings('.submit-link-msg').html(resp.msg.msg);
        }
      },
      error: function() {
        $(area).siblings('.loading-image').hide();
        $(area).siblings('.btn').show();
        alert('an error occured');
      }
    });
  });
  $('#title, #proposalIntroduction, .opiniontext').keyup(function() {
    actionOnKeyUpEvent($(this));
  });
  $('#title, #proposalIntroduction, .opiniontext').keydown(function() {    
    switch ($(this).attr('id')) {
      case 'title' :
        $(this).attr('maxlength', tcharLimit);
        break;
      case 'proposalIntroduction' :
        $(this).attr('maxlength', icharLimit);
        break;
      case 'opiniontext' :
        $(this).attr('maxlength', ocharLimit);
        break;
    }
  });
  $('#saveProposal').click(function(e) {
    e.preventDefault();
    var title = $('#title').val();
    var introduction = $('#proposalIntroduction').val();
    var body = $('#proposalBody').val();
    if ($.trim(title) == '') {
      $('#proposal-error').show();
      $('#proposal-error').html(Yii.t('js', ' Proposal title can not be empty'));
      return false;
    }
    if ($.trim(introduction) == '') {
      $('#proposal-error').show();
      $('#proposal-error').html(Yii.t('js', ' Proposal introduction can not be empty'));
      return false;
    }
    if ($.trim(body) == '') {
      $('#proposal-error').show();
      $('#proposal-error').html(Yii.t('js', ' Proposal can not be empty'));
      return false;
    }
    //checking session active/inactve of user.
    var msg = Yii.t('js', 'Your session has expired.');
    msg += " " + Yii.t('js', 'Press Cancel to stay on page. Press Ok to redirect to home page.');
    checkSession(msg);
    $(this).parents('.modal').modal('hide');
    $('#yes').click(function() {
      $('#yes').attr("disabled", true);
      $('#submit-proposal-form').submit();
    });
    $('#no').click(function() {
      $(this).parents('.modal').modal('hide');
      $('#formModal').modal('show');
    });
  });
  $('.tmodal-close').click(function() {
    $('#opinion-msg').html('');
    $('.tmodal-launcher').show();
    $('.triangle-text').show();
    $('.sl').show();
    $('.tmodal-background').hide();
  });
  $('.sl').click(function() {
    $('#opinion-msg').html('');
    $('#position-msg').text(Yii.t('js','Thank you for giving us your position on the proposal.')).addClass('alert-success');
  });
  $('.closePopOver').click(function() {
    $('.opinion').popover('hide');
  });
  $(document).on('click', '.hidePopOver', function() {
    $('.popover').hide();
  });  

  openPreviousLinkModal();
  $('.link-page-login').click(function() {
    var proposalClass = '';
    var appliedClass = $(this).attr('class').split(' ');
    for (i = 1; i < appliedClass.length; i++) {
      if (appliedClass[i].substr(0, 4) == 'pid-') {
        proposalClass = '?page=link' + appliedClass[i];
        break;
      }
    }
    var pathname = window.location.pathname;
    $.pageslide({direction: 'left', href: page.base_url + 'login?back=' + pathname + proposalClass});
    return false;
  });
  $('.opinion-page-login').click(function() {
    var proposalClass = '';
    var appliedClass = $(this).attr('class').split(' ');
    for (i = 1; i < appliedClass.length; i++) {
      if (appliedClass[i].substr(0, 4) == 'pid-') {
        proposalClass = '?page=opinion' + appliedClass[i] ;
        break;
      }
    }
    var pathname = window.location.pathname;
    $('#modalBox').modal('hide');
    $.pageslide({direction: 'left', href: page.base_url + 'login?back=' + pathname + proposalClass});
    return false;
  });  
  $("#title, #proposalIntroduction , .opiniontext").bind('paste', function(e) {
    actionOnPasteEvent($(this));
  });

  $('.add-new-proposal-btn').click(function() {
    $('#formModal').modal('hide');
  });
  
  $(document.body).on('click', '.popover-test', function(e) {
    initializePopover();
    e.preventDefault();
  });
  
  $('#modalBox').on('click' ,'.save-answer', function(e) {
    e.preventDefault();    
    saveAnswerForOpinion($(this));
  });
});
function chopUrl(url) {
  if (url == '') {
    return url;
  }
  var length = url.length;
  if (length > 35) {
    url = url.substring(0, 35) + '...';
  }
  return url;
}

function addHttpInUrl(url) {
  if (url == '') {
    return url;
  }
  if (!url.match(/^http([s]?):\/\/.*/)) {
    url = 'http://' + url;
  }
  return url;
}

function preserveNewLine(text) {
  if (text == '') {
    return text;
  }
  var regX = /\n/gi;
  str = new String(text);
  str = str.replace(regX, "<br /> \n");
  return str;
}

function closePanelContainer() {
  if ($('.panelcontainer2').is(":visible")) {
    $('.panelcontainer2').slideUp(400);
  }
  if ($('.panelcontainer').is(":visible")) {
    $('.panelcontainer').slideUp(400);
  }
}

function removeActiveClass() {
  $('.active').each(function() {
    if ($(this).attr('class').indexOf("proposal") >= 0) {
      $(this).attr('class', 'proposal by-' + $(this).children('.auID').val());
    }
  });
}

function decodeHtml(string) {
  return $("<span />", {html: string}).text();
}

function openPreviousLinkModal() {
  var pageName = window.location.search.split('=');
  if (pageName != '') {     
    if (pageName[1].substr(4, 4) == 'pid-') {
       $('.' + pageName[1].substr(4, pageName[1].length)).children('.openModal').trigger('click');
      $('#modalBox').children('.modal-dialog').children('.modal-content').children('.modal-body').children('.modal-menu-bar').children("li").each(function() {
        $(this).removeClass('active');
        if ($(this).children('a').attr('href') == '#links') {
          $(this).addClass('active');
        }
      });
      $('#proposal').removeClass('active');
      $('#opinion').removeClass('active');
      $('#links').addClass('active');
    }
    if (pageName[1].substr(7, 4) == 'pid-') { 
      $('.' + pageName[1].substr(7, pageName[1].length)).children('.openModal').trigger('click'); 
      $('#modalBox').children('.modal-dialog').children('.modal-content').children('.modal-body').children('.modal-menu-bar').children("li").each(function() {
        $(this).removeClass('active');
        if ($(this).children('a').attr('href') == '#opinion') {
          $(this).addClass('active');
        }
      });
      $('#proposal').removeClass('active');
      $('#opinion').addClass('active');
      $('#links').removeClass('active');
    }
  }
}

function prepareAnswerHtml(opinionId) {
  var html = '<p>\n\
                <input type="hidden" name="opinion_id" class="answer-opinion-id' + '" value = "' + opinionId + '"/>\n\
                <a href="#" role="button" class="popover-test" title="" data-original-title="' + Yii.t('js', 'Your Reply') + ' ">'
                  + Yii.t('js', 'Reply') + '\n\
                </a>\n\
              </p>';
  return html;
}
function initializePopover() {
  $('.popover-test').popover({
    content: '<form role="form">\n\
                <fieldset>\n\
                  <div class="opinion-answer-error hidden alert-danger"></div>\n\
                  <div class="form-group">\n\
                    <textarea class="form-control" rows="5" placeholder="aggiungi la tua opinione..."></textarea>\n\
                  </div>\n\
                  <button class="btn btn-primary save-answer">' + Yii.t('js', 'submit') + '</button>\n\
                  <span>\n\
                    <img class="hidden save-comment-processing-image" src="' + imagePath + 'loader.gif" alt="' + Yii.t('js', 'processing') +'..." />\n\
                  </span>\n\
                </fieldset>\n\
              </form>',
    html: true
  });
}

function saveAnswerForOpinion(self) {
  var opinionId = $(self).parents('.popover:first').siblings('.answer-opinion-id').val();
  var loadingImage = $(self).siblings('span').find('img');
  var opinionAnswerError = $(self).siblings('.opinion-answer-error');
  var articleClass = $(self).parents('#modalBox:first').siblings('article').attr('class');  
  var proposalId = articleClass.split('pid-')[1];
  $(opinionAnswerError).addClass('hidden');
  var submittedAnswer = $(self).siblings('.form-group').find('textarea').val();
  var profileUrl = $(self).parents('#modalBox:first').siblings('article').find('.profileUrl').val();
  if ($.trim(submittedAnswer) == '') {
    $(opinionAnswerError).removeClass('hidden');
    $(opinionAnswerError).html(Yii.t('js', 'Reply can not be empty'));
    return false;
  }
  $(self).hide();
  $(loadingImage).removeClass('hidden');
  $.ajax({
    type: 'POST',
    dataType: 'json',
    url: baseUrl + 'discussion/proposal/opinion/answer',
    data: { 
      opinion_id: opinionId,
      submitted_answer: submittedAnswer,
      author_name: loginUsername,
      author_slug: loginUserslug,
      proposal_id: proposalId
    },
    success: function(resp) {
      $(loadingImage).addClass('hidden');
      $(self).show();
      $(opinionAnswerError).removeClass('hidden alert-danger');
      if (resp.success) {
        $(opinionAnswerError).addClass('alert-success');
        $(opinionAnswerError).html(resp.msg);
        var answerHtml = '<hr><p class="small">' + resp.data + ' - <strong>' + loginUsername + '</strong></p>';
        $(self).parents('.popover').parent('p').before(answerHtml);
      } else {
        $(opinionAnswerError).addClass('alert-danger');
      }
      $('[data-original-title]').popover('hide');
    },
    error: function() {
      $(loadingImage).addClass('hidden');
      $(self).removeClass('hidden');
      $(opinionAnswerError).addClass('hidden');
      alert(Yii.t('js', 'An error occured'));
    }
  });
}

function setOpinionComment() {
  var index = $('.index').val();
  var author = $('#author').val();
  var authorid = $('#authorid').val();
  var image = $('#authorImage').val();
  var textarea = $('#opinion-box-text').val();
  var opinionId = $('.id').val();
  var opinionClassText = '';
  if (index != '') {
    if ($.inArray(parseInt(index), neutral) !== -1) {
      opinionClassText = 'neutral';
    } else if ($.inArray(parseInt(index), agree) !== -1) {
      opinionClassText = 'agree';
    } else {
      opinionClassText = 'disagree';
    }
  }

  var opinionClass = 'class = "row  ' + opinionClassText + '"';
  var div = '<div ' + opinionClass + '>\n\
              <div class="col-lg-2 col-md-2 col-sm-2 col-xs-3">\n\
                <img class="img-responsive img-circle" src="' + image + '"/>\n\
              </div>\n\
              <div class="col-lg-10 col-md-10 col-sm-10 col-xs-9">\n\
                <p><strong><a href=' + authorid + '>' + author + '</a></strong>'
  if (textarea != '') {
    div += ': <description>' + textarea + '</description>';
  }
  var isAddedAuthorOpinion = false;
  var commentInOpinionBox = $('#opinion').children('.opinionbox').children('.row').length;
  if (commentInOpinionBox == 1 && typeof($('#opinion').children('.opinionbox').children('.row').find("strong").html()) == "undefined") {
    var answerHtml = '';
    if (isCurator == true || authorSlug == loginUserslug) {
      answerHtml = prepareAnswerHtml(opinionId);
    }
    answerHtml += '</div></div>';
    $('#opinion').children('.opinionbox').html("<hr>" + div + answerHtml);
    isAddedAuthorOpinion = true;
  } else {
    $('#opinion').children('.opinionbox').children('.row').each(function() {
      if ($.trim($(this).find("strong > a").html()) == $.trim(author)) {
        var answerHtml = '';
        if (typeof($(this).find('.small').html()) != 'undefined') {
          $(this).find('.small').each(function() {
            answerHtml += '<hr><p class="small">' + $(this).html() + '</p>';
          });
        }
        if (isCurator == true || authorSlug == loginUserslug) {
          answerHtml +=  prepareAnswerHtml(opinionId);
        }
        answerHtml += '</div>';
        $(this).replaceWith(div + answerHtml + '</div>');
        isAddedAuthorOpinion = true;
      }
    });
  }
  if (isAddedAuthorOpinion == false) {
    if (isCurator == true || authorSlug == loginUserslug) {
      div += prepareAnswerHtml(opinionId);
    }
    div += '</div>';
    $("<hr>" + div).prependTo($('#opinion').children('.opinionbox'));
  }
  initializePopover();
}

function saveTrianglePostion(index, understanding, comprehension, msg) {
  var url = document.URL;
  var urlPart = url.split('?');
  if (urlPart[0] === '') {
    return false;
  }
  urlPart[0] = urlPart[0].replace('#', '');
  var proposalId = '';
  var appliedClass = $('#modalBox').siblings('.proposal').attr('class').split(' ');
  for (i = 1; i < appliedClass.length; i++) {
    if (appliedClass[i].substr(0, 4) === 'pid-') {
      proposalId = appliedClass[i].substr(4);
      break;
    }
  }
  var opinionId = $('.id').val();
  var update = $('.upd').val();
  var prevIndex = $('.previndex').val();
  $('#opinion-button').hide();
  var loadingImageHtml = $('.loading-image').html();
  $('.triangle-position-text').html(loadingImageHtml);
  var opinionDescription = $('#opinion-box-text').val();
  $.ajax({
    type: 'POST',
    dataType:'json',
    url: urlPart[0]  + '/saveposition',
    data: {
      index: index,
      understanding: understanding,
      comprehension: comprehension,
      id: proposalId,
      opinionid: opinionId,
      update: update,
      previndex: prevIndex,
      opiniontext: opinionDescription
    },
    success: function(resp) {
      $('#opinion-button').show();
      $('.triangle-position-text').html(msg);
      if (resp.success) {
        $('.id').val(resp.data.opinion_id);
        $('.upd').val(1);
        $('.previndex').val(index);
      } else {
        alert('An error occured, please try again');
      }
      if (resp.data != 'undefined') {
        setOpinionCountAndHeatMap(resp.data.opinion);
      }
    },
    error: function() {
      $('.triangle-position-text').html('');
      $('#opinion-button').show();
      alert('An error occured, please try again');
    }
  });
  if (update == 1 && opinionDescription != '') {
    setOpinionComment();
  }
}

function setOpinionCountAndHeatMap(opinion) {
  var heatMap = '';
  if (opinion) {
    heatMap = '<svg xml:space="preserve" enable-background="new -18.25 -18.75 200 180" viewBox="-18.25 -18.75 200 180" height="180px" width="200px" y="0px" x="0px" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" version="1.1">\n\
              <text class="triangle-text" transform="matrix(1 0 0 1 63 -5.25)"  font-family="HelveticaNeue" font-size="11">Chiara</text>\n\
              <text class="triangle-text" transform="matrix(1 0 0 1 51 156.75)"  font-family="HelveticaNeue" font-size="11">Non Chiara</text>\n\
              <text class="triangle-text" transform="matrix(1 0 0 1 -13.4998 77)"  font-family="HelveticaNeue" font-size="11"><tspan x="10" y="10">Non</tspan><tspan x="10" y="20">concordo</tspan></text>\n\
              <text class="triangle-text" transform="matrix(1 0 0 1 126 77)"  font-family="HelveticaNeue" font-size = "11" >Concordo</text>\n\
              <g>';
    for (key in triangles) {
      heatMap += '<polygon class = "sl" index = "' + key + '" understanding = "' + triangles[key].a + '" comprehension = "' + triangles[key].c + '" fill = "' + colors[opinion[key]] + '" points = "' + triangles[key].points + '" msg = "' + triangles[key].msg + '" />';
    }
    heatMap += '</g></svg>';
  }
  $('.active-parent').children('.proposal').children('footer').children('.opinions').children('count').text(opinion.OpinionCount);
  $('.active-parent').children('.proposal').children('footer').children('.documeents').children('.pop').html(heatMap);
}


function actionOnKeyUpEvent(self) {
  titleCount = '';
  proposalCount = '';
  switch (self.attr('id')) {
    case 'title':
      titleCount = tcharLimit - self.val().length;
      self.siblings('.words').text(titleCount);
      if (titleCount < 0) {
        $('#proposal-error').html(Yii.t('js', 'Title should be atmost 50 characters'));
      }
      break;
    case 'proposalIntroduction' :
      proposalCount = icharLimit - self.val().length;
      self.siblings('.words').text(proposalCount);
      if (proposalCount < 0) {
        $('#proposal-error').html(Yii.t('js', 'Proposal Introduction should be atmost 500 characters'));
      }
      break;
    case 'opiniontext' :
      opinionCount = ocharLimit - self.val().length;
      self.siblings('.words').text(opinionCount);
      if (opinionCount < 0) {
        $('#opinion-msg').html(Yii.t('js', 'Opinion text should be atmost 500 characters'));
        $('#opinion-msg').addClass('alert-danger');
        $('#opinion-button').attr('disabled','true');
      } else {
        $('#opinion-msg').html('');
        $('#opinion-msg').removeClass('alert-danger');
        $('#opinion-button').removeAttr('disabled');
      }
      break;
  }
  if (titleCount <= tcharLimit && titleCount >= 0 && proposalCount <= icharLimit && proposalCount >= 0) {
    $('#proposal-error').hide();
    $('#proposal-error').html('');
    $('#saveProposal').removeAttr('disabled');
  } else if (titleCount >= 0 && titleCount <= tcharLimit) {
    if (proposalCount < 0) {
      $('#proposal-error').html(Yii.t('js', 'Proposal Introduction should be atmost 500 characters'));
      $('#proposal-error').show();
      $('#saveProposal').attr('disabled','true');
    }
  } else if (proposalCount >= 0 && proposalCount <= icharLimit) {
    if (titleCount < 0) {
      $('#proposal-error').html(Yii.t('js', 'Title should be atmost 50 characters'));
      $('#proposal-error').show();
      $('#saveProposal').attr('disabled','true');
    }
  }
}

function actionOnPasteEvent(self) {
  titleCount = '';
  proposalCount = '';
  self.attr('maxlength', '');
  setTimeout(function() {
    var chars = parseInt(self.siblings('.words').text());
    var pastedText = self.val();
    var pastedLength = pastedText.length;
    switch (self.attr('id')) {
      case 'title' :
        titleCount = tcharLimit - pastedLength;
        if (titleCount < 0) {
          $('#proposal-error').html(Yii.t('js', 'Title should be atmost 50 characters.'));
          $('#proposal-error').show();
          $('#saveProposal').attr('disabled','true');
        }
        self.siblings('.words').text(titleCount);
        break;
      case 'proposalIntroduction' :
        proposalCount = icharLimit - pastedLength;
        if (proposalCount < 0) {
          $('#proposal-error').html(Yii.t('js', 'Proposal Introduction should be atmost 500 characters.'));
          $('#proposal-error').show();
          $('#saveProposal').attr('disabled','true');
        }
        self.siblings('.words').text(proposalCount);
        break;
      case 'opiniontext' :
        chars = ocharLimit - pastedLength;
        self.siblings('.words').text(chars);
        break;
    }
    if (titleCount <= tcharLimit && titleCount >= 0 && proposalCount <= icharLimit && proposalCount >= 0) {
      $('#proposal-error').hide();
      $('#proposal-error').html('');
      $('#saveProposal').removeAttr('disabled');
    }
  }, 100);
}

function prepareProposalVideoImageHtml(area) {
  var proposalHtml = '';
  if (attacheImageOnProposal == 1) {
    var proposalImage = $(area).siblings('.image').val();console.log(proposalImage);
    if (proposalImage != '' && proposalImage != 'undefined') {
      proposalHtml += '<hr><div class="proposal-image"><h4>' + Yii.t('js', 'Proposal Image') + '</h4>';
      proposalHtml += '<img src="' + proposalImage + '" height="250" class="img-responsive" alt="proposal image"/></div>';
    }
  }
  return proposalHtml;
}