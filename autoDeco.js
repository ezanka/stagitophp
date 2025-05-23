let timeout;

function resetTimer() {
    clearTimeout(timeout);
    timeout = setTimeout(activateCooldown, 300000); 
    
    fetch('index.php?action=reset');
}

function activateCooldown() {
    window.location.href = 'signin.php?error=Session expirée, Cause : inactivité sur 300 secondes';
}

document.addEventListener('mousemove', resetTimer);
document.addEventListener('keydown', resetTimer);
document.addEventListener('scroll', resetTimer);

resetTimer();