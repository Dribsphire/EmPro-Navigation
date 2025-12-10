const toggleButton = document.getElementById('toggle-btn')
const sidebar = document.getElementById('sidebar')

function toggleSidebar(){
  sidebar.classList.toggle('close')
  toggleButton.classList.toggle('rotate')

  closeAllSubMenus()
  
  // Resize map when sidebar toggles to fix UI layout
  // Wait for CSS transition to complete (300ms as per CSS)
  setTimeout(() => {
    resizeMap()
  }, 350) // Slightly longer than CSS transition (300ms)
}

// Function to resize map (used by sidebar toggle and window resize)
function resizeMap() {
  if (window.map && typeof window.map.resize === 'function') {
    window.map.resize()
    console.log('Map resized')
  }
}

// Also resize map on window resize events
window.addEventListener('resize', () => {
  resizeMap()
})

function toggleSubMenu(button){

  if(!button.nextElementSibling.classList.contains('show')){
    closeAllSubMenus()
  }

  button.nextElementSibling.classList.toggle('show')
  button.classList.toggle('rotate')

  if(sidebar.classList.contains('close')){
    sidebar.classList.toggle('close')
    toggleButton.classList.toggle('rotate')
  }
}

function closeAllSubMenus(){
  Array.from(sidebar.getElementsByClassName('show')).forEach(ul => {
    ul.classList.remove('show')
    ul.previousElementSibling.classList.remove('rotate')
  })
}