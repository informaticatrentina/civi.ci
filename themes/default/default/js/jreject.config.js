jQuery(document).ready(function() {
  $.reject({
    reject: {
      msie: 8
    },
    header: '', // Header Text
    paragraph1: 'Sito ottimizzato per browser di ultima generazione quali Chrome, Firefox, Safari; Internet Explorer versione 9 o superiore. Con versioni precedenti si potrebbero verificare problemi di visualizzazione.', // Paragraph 1  
    paragraph2: 'È preferibile aggiornare il browser a una versione più recente!',
    closeLink: 'Chiudi',
    closeMessage: '', // Message below close window link
    display: ['firefox', 'chrome', 'msie'],
    imagePath: './themes/default/images/'
  });
});
