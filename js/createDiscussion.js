$(document).ready(function() {
   $('#home').find("a").removeClass('candgselected');
   $('#createContest').find("a").addClass('candgselected');
   $('#create').click(function() {
        if ($('#discussionTitle').val() == '') {
            $('#error').html(Yii.t('js', "Please enter Discussion Title")).css('color', 'red');
            return false;
        }
        if ($('#discussionSummary').val() == '') {
            $('#error').html(Yii.t('js', "Please enter Discussion Summary")).css('color', 'red');
            return false;
        }
    });
});


