// Functions and javascript for other widget page
function participantCount() {
    // Get shortcode name
    shortcodeName = 'HTX_participantCount';
    form = document.getElementById('form_choose_participantCount').value;
    liveUpdate = $('#liveUpdate_participantCount').is(":checked") ? 1 : 0;
    countDown = $('#coundown_participantCount').is(":checked") ? 1 : 0;
    countDownFrom = document.getElementById('max_participantCount').value;
    exampleNumber = document.getElementById('example_participantCount').value;

    if (exampleNumber == null || exampleNumber=="") exampleNumber = 50;
    if (countDownFrom == null || countDownFrom=="") countDownFrom = 50;
    
    // Set extra parameter
    extraShortcode = " form="+form;
    if (liveUpdate == 1) {
        extraShortcode += " live=true";
    }
    if (countDown == 1) {
        extraShortcode += " countDown=true countDownFrom="+countDownFrom;
    }
    
    

    // Insert shortcode
    shortCode = "["+shortcodeName+extraShortcode+"]";
    document.getElementById('widget_shortcode_participantCount').innerHTML = shortCode;

    // Insert example
    if (countDown == 1) {
        exampleNumber = countDownFrom - exampleNumber;
    }

    if (exampleNumber <= 0) exampleNumber = 0;
    document.getElementById('widget_example_participantCount').innerHTML = exampleNumber;

}