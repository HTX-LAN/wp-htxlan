// Script for teams page
function showTeamColumnSettings() {
    element = document.getElementById('columnShownEditPage');
    if (element.classList.contains('columnShownEditPage_closed')) {
        element.classList.remove('columnShownEditPage_closed');
    } else {
        element.classList.add('columnShownEditPage_closed');
    }
}