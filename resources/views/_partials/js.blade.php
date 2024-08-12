<!-- Bootstrap 4 -->
<script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>
<script src="{{ asset('template/dist/js/waitMe.js') }}"></script>
<script>
    $(window).on('load', function() {
        $("#content-body").waitMe("hide");
    })
    $(window).bind("beforeunload", function() {
        $("#content-body").waitMe({
            effect: "roundBounce",
            // text: "<span class='text-primary'>Loading...</span>",
            bg: "rgba(255,255,255,0.7)",
            // color: "#1c769b",
            maxSize: "",
            waitTime: -1,
            textPos: "vertical",
            fontSize: "",
            source: "",
            onClose: function() {},
        });

        // Check when download completes by polling the document state
        // var checkDownloadComplete = setInterval(function() {
        //     if (document.readyState === "complete") {
        //         clearInterval(checkDownloadComplete);
        //         $("#content-body").waitMe("hide");
        //     }
        // }, 1000);
    });
</script>
