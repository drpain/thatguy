function wrapText(elementID, openTag, closeTag) {
    var textArea = $('#' + elementID);
    var len = textArea.val().length;
    var start = textArea[0].selectionStart;
    var end = textArea[0].selectionEnd;
    var selectedText = textArea.val().substring(start, end);
    var replacement = openTag + selectedText + closeTag;
    textArea.val(textArea.val().substring(0, start) + replacement + textArea.val().substring(end, len));
}



$(document).ready(function() {


    $("#menu-toggle").click(function(e) {
        e.preventDefault();
        $("#wrapper").toggleClass("active");
        $("#menu-toggle").children('i').toggleClass("glyphicon-align-justify");
        $("#menu-toggle").toggleClass("active");
        $("#menu-toggle").children('i').toggleClass("glyphicon-remove");
    });


    $(".updateImage").click(function(e) {
        var getId = $(this).attr("data-id");
        form = $("form[name='myForm" + getId + "']").serialize();
        $.post( "portfolio.php", form, function( data ) {

            console.log(data);

            // On a success, remove the modal, and color the tile
            if (data == "success")
            {
                $(".modal").modal("hide");
                //$("span.portfolioTiles[data-id='" + getId + "']").hide();
                $("img.img-thumbnail[data-id='" + getId + "']").css({'border' : '1px solid #1b3c55', 'background' : '#d7e0e8'}).fadeTo("fast" , 0.9);
            }
        });
    });

    // Unhide some things when their content changes
    /* Settings.php */
    $('input[name="user-email"]').focus(function(event){
        $("#emailWarning").show();
    });

    $('input[name="user-password"]').focus(function(event){
        $("#passwordWarning").show();
    });
    /* Settings.php */

    /* User */
    $('.iHide').hide(); // want to hide it myself
    // Otherwise lets check for changes and then post them through
    $('form.users_update_group').change(function(event) {
       $(this).submit();
    });
    $('form.users_update_email').change(function(event) {
       $(this).submit();
    });
    $('form.users_update_password').change(function(event) {
       $(this).submit();
    });
    $('form.users_update_activation').change(function(event) {
       $(this).submit();
    });
    /* User */


    /* BLOG */
    // This is going to be used wherever I want to edit a comment or something by double clicking it.
    // You will double-click the div and it will show the form and hide the div
    $(".editDiv").dblclick(function(event) {
        event.preventDefault();
        $(this).next("form['name=editForm']").show();
        $(this).next(".editForm").show();
        $(this).hide();
    });


    $(".editLink").click(function(event) {
        event.preventDefault();
        $(this).next(".editForm").show();
    });


    $('.bbcode').click(function(event){
        event.preventDefault();
        var pre = $(this).children('.pre').html();
        var post = $(this).children('.post').html();
        wrapText('text', pre, post);
    });


    $('input[id=lefile]').change(function() {
       $('#fileName').val($(this).val());
       alert("Clicked");
    });


    $('.insertImage').click(function(event) {
        event.preventDefault();
        var imageSource = $(this).attr('src');
        var largeImage = imageSource.replace("thumbs/", '');
        var getText = $("#text").val();
        $("#text").val(getText + " [img]" + largeImage + "[/img]");
    });


    // Blog Remove image
    $('.removeBlogImage').click(function(event) {
        event.preventDefault();
        var getSrc = $(this).attr('data-src');
        $.post("blog_edit.php", { action: "unlink", img: getSrc }, function( data ) {


            if (data == "true")
            {
                var getText = $("#text").val();
                var newText = getText.replace("[img]"+getSrc+"[/img]", '');
                $('#text').val(newText);
            }

            else
            {
                alert("Weird, unable to delete the image");
            }
        });
        $(this).parent("div").remove();
    });


	$("#container").attr("data-value", $("#container").width());

    $('input[type=file]').bootstrapFileInput();
    $('.file-inputs').bootstrapFileInput();


    // Hook into the confirm plugin for general actions
    $(".confirm").click(function(event){

        event.preventDefault();
        var link = $(this).attr('href');

        // Inject a Popover
        var buttons = '<a class="confirm btn btn-primary" href="' + link + '">'
            +   '<span style="color: white;" class="glyphicon glyphicon-ok"></span>'
            + '</a>'
            + '<button class="cancel btn btn-default" type="button" data-dismiss="modal">'
            +   '<span style="color: white;" class="glyphicon glyphicon-remove"></span>'
            + '</button>';

        var modalHTML = '<div class="modal fade">'
            + '<div class="modal-dialog">'
            + '<div class="modal-content">'
            + '<div class="modal-header">'
            + '<a href="#" class="close" data-dismiss="modal">'
            + '<span style="color: white;" class="glyphicon glyphicon-remove"></span>'
            + '</a>'
            + '<h4 class="modal-title">Please confirm</h4>'
            + '</div>'
            + '<div class="modal-body">Are you sure you want to continue?</div>'
            + '<div class="modal-footer">' + buttons + '</div>'
            + '</div>'
            + '</div>'
            + '</div>';

        var modal = $(modalHTML);

        // Show the modal
        $("body").append(modal);
        modal.modal();
    });


    // Hook into the confirm plugin for deletions
    $(".confirmDelete").click(function(event){

        event.preventDefault();
        var link = $(this).attr('href');

        // Inject a Popover
        var buttons = '<a class="confirm btn btn-primary" href="' + link + '">'
            +   '<span style="color: white;" class="glyphicon glyphicon-ok"></span>'
            + '</a>'
            + '<button class="cancel btn btn-default" type="button" data-dismiss="modal">'
            +   '<span style="color: white;" class="glyphicon glyphicon-remove"></span>'
            + '</button>';

        var modalHTML = '<div class="modal fade">'
            + '<div class="modal-dialog">'
            + '<div class="modal-content">'
            + '<div class="modal-header">'
            + '<a href="#" class="close" data-dismiss="modal">'
            + '<span style="color: white;" class="glyphicon glyphicon-remove"></span>'
            + '</a>'
            + '<h4 class="modal-title">Please confirm</h4>'
            + '</div>'
            + '<div class="modal-body">Are you sure you want to delete the entry?</div>'
            + '<div class="modal-footer">' + buttons + '</div>'
            + '</div>'
            + '</div>'
            + '</div>';

        var modal = $(modalHTML);

        // Show the modal
        $("body").append(modal);
        modal.modal();
    });


    /* BLOG */
    $('.thumbnailOverlay').hide();
    $('.thumbnail').hover(function(){
        $(this).children('.thumbnailOverlay').fadeTo(300, 0.8);
        event.preventDefault();
    },function(){
        $(this).children('.thumbnailOverlay').fadeTo(300, 0.0).hide();
    });


    /* GENERAL */
    // Focus on the default field by adding the focus class to it
    $('.focus').focus();
    $('.hideMe').hide();
    /* BLOG */
    $('.thumbnailOverlay').hide();
    $('.thumbnail').hover(function(){
        $(this).children('.thumbnailOverlay').fadeTo(300, 0.8);
        event.preventDefault();
    },function(){
        $(this).children('.thumbnailOverlay').fadeTo(300, 0.0).hide();
    });


    /* GENERAL */
    // Focus on the default field by adding the focus class to it
    $('.focus').focus();
    $('.hideMe').hide();

});

/* GENERAL */