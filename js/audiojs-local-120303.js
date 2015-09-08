/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


jQuery(document).ready(function($) {

    // detect ios, do not autoplay (as ios does not support)
    var deviceAgent = navigator.userAgent.toLowerCase();
    var agentID = deviceAgent.match(/(iphone|ipod|ipad)/);
    if (agentID) {
        soundAutoPlay = false;
        quizShowAudioControls = true;
    }

    if (quizShowAudioControls) {
        $('div.audioplay').show();
    }
    
    // setup the audio player
    var a = audiojs.createAll({
        css: false,
        trackEnded: function() {
            $('div.audioplay').removeClass('playing');
            if (($(player).data('playlist').length) > 0) {
                autoplayaudio(player, 3000);
            } else {
                if (quizAutoProgress == true) {
                    setTimeout(function() {
                        window.navigatenext();
                    },4000);
                }
            }
        }
    });
    
    // initialize autoplay
    var player = a[0];
    $(player).data('playlist', $('div.audioplay'))
    
    // start autoplay
    if (soundAutoPlay == true) {
        autoplayaudio(player, 3000);
    }
    
    // load and play on click
    $('div.audioplay').click(function(e) {
        // clear the playlist and cancel autoprogress
        $(player).data('playlist', '');
        quizAutoProgress = false;
        
        if ($(this).hasClass('playing')) {
            // pause if is playing
            $('div.audioplay').removeClass('playing');
            player.pause();
        } else {
            // play the audio 
            $('div.audioplay').removeClass('playing');
            player.load($(this).attr('data-src'));
            $(this).addClass('playing');
            player.play();
        }
    });
});

/*
 * autoplayes the playlist
 * the playlist is defined in a jquery data element on the playerobject
 */
function autoplayaudio(player, delay) {
    $('div.audioplay').removeClass('playing');
    
    // load the playlist
    var audiodivs = $(player).data('playlist');
    
    // find the current element and source
    var curaudiodiv = audiodivs.first();
    var audiosource = curaudiodiv.attr('data-src');
    
    // load and play
    player.load(audiosource);
    setTimeout(function() {
        curaudiodiv.addClass('playing');
        player.play();
    }, delay);
    
    // clear current element from playlist
    audiodivs = audiodivs.slice(1, audiodivs.length);
    $(player).data('playlist', audiodivs);
    
    return true;
}