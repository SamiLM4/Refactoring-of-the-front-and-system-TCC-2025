async function loadLayout() {

    const sidebar = await fetch("./components/sidebar.html")
    const navbar = await fetch("./components/navbar.html")

    const sidebarHtml = await sidebar.text()
    const navbarHtml = await navbar.text()

    document.getElementById("sidebar").innerHTML = sidebarHtml
    document.getElementById("navbar").innerHTML = navbarHtml

}