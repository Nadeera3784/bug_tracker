document.getElementById('reportBug').addEventListener('click', function() {
    document.getElementById('bugForm').style.display = 'block';
});

document.getElementById('submitBug').addEventListener('click', function() {
    var title = document.getElementById('bugTitle').value;
    var description = document.getElementById('bugDescription').value;
    var urgency = document.getElementById('bugUrgency').value;
    
    fetch('/api/submit_bug.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            title: title,
            description: description,
            urgency: urgency
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('bugForm').style.display = 'none';
            alert('Thank you for submitting a bug report!');
            
            // Start polling for updates
            pollForUpdates(data.bug_id);
        }
    });
});

function pollForUpdates(bugId) {
    setInterval(function() {
        fetch('/api/get_updates.php?bug_id=' + bugId)
        .then(response => response.json())
        .then(data => {
            if (data.update) {
                showNotification(data.message);
            }
        });
    }, 5000); // Poll every 5 seconds
}

function showNotification(message) {
    var notification = document.getElementById('notification');
    notification.innerHTML = message;
    notification.style.display = 'block';
    setTimeout(function() {
        notification.style.display = 'none';
    }, 5000);
}