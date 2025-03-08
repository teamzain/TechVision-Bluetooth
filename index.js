document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    let sidebar = document.querySelector(".sidebar");
    let sidebarBtn = document.querySelector(".bx-menu");

    sidebarBtn.addEventListener("click", () => {
        sidebar.classList.toggle("close");
    });

    // Sub-menu Toggle
    let arrow = document.querySelectorAll(".arrow");
    for (var i = 0; i < arrow.length; i++) {
        arrow[i].addEventListener("click", (e) => {
            let arrowParent = e.target.parentElement.parentElement; //selecting main parent of arrow
            arrowParent.classList.toggle("showMenu");
        });
    }

    // Fetch total connected devices
    fetch('fetch_device.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-connected-devices').textContent = data.total_connected_devices;
        })
        .catch(error => console.error('Error fetching connected devices:', error));

    // Profile dropdown toggle
    const profilePic = document.querySelector('.profile-pic');
    const dropdownMenu = document.querySelector('.dropdown-menu');

    // Toggle the dropdown on profile picture click
    profilePic.addEventListener('click', function () {
        dropdownMenu.classList.toggle('show-dropdown');
    });

    // Close the dropdown if user clicks outside
    document.addEventListener('click', function (event) {
        if (!profilePic.contains(event.target)) {
            dropdownMenu.classList.remove('show-dropdown');
        }
    });
});
