$("#hdocsave").click(function () {

    $.ajax({
        method: "POST",
        url: OC.generateUrl("apps/hedgenext/settings/post"),
        data: $("#hdocurl").serialize(),
    });
})
$("#clickkey").click(function () {
    $("#hiddenkey").show();
    $("#clickkey").hide();
})