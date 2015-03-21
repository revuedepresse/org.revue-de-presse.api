(function () {
    if (navigator.mozApps) {
        var manifest_url = location.origin + '/mobile/manifest.webapp';
        var installCheck = navigator.mozApps.checkInstalled(manifest_url);

        installCheck.onsuccess = function () {
            // get a reference to the button and call install() on click if the app isn't already installed.
            // If it is, hide the button.
            var button = document.getElementById('install-weaver');
            if (!installCheck.result) {
                button.classList.remove('hide');
                button.addEventListener('click', install, false);
            }
        };

        function install(event) {
            event.preventDefault();
            // define the manifest URL
            // install the app
            var installLocFind = navigator.mozApps.install(manifest_url);
            installLocFind.onsuccess = function (data) {
                // App is installed, do something
            };
            installLocFind.onerror = function () {
                // App wasn't installed, info is in
                // installapp.error.name
                alert(installLocFind.error.name);
            };
        };
    }
})();
