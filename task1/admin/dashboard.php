<?php
include '../includes/config.php';
include '../includes/auth.php';
include '../includes/db.php';

if (!isAuthenticated()) {
    header('Location: admin/index.php');
    exit();
}

$db = getDbConnection();

$bugs = getBugList($db);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <?php include '../includes/header.php'; ?>
    <script type="text/javascript" src="https://js.pusher.com/7.0/pusher.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6 text-gray-800">Bug Tracker Dashboard</h1>
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <table id="bugTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Urgency</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if(isset($bugs)) :?>
                        <?php while ($bug = $bugs->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $bug['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= htmlspecialchars($bug['title']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-<?= $bug['urgency'] == 'High' ? 'red' : ($bug['urgency'] == 'Medium' ? 'yellow' : 'green') ?>-100 text-<?= $bug['urgency'] == 'High' ? 'red' : ($bug['urgency'] == 'Medium' ? 'yellow' : 'green') ?>-800">
                                    <?= $bug['urgency'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $bug['status'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <select class="statusSelect mr-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" data-bug-id="<?= $bug['id'] ?>">
                                    <option value="New" <?= $bug['status'] == 'New' ? 'selected' : '' ?>>New</option>
                                    <option value="In Progress" <?= $bug['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                    <option value="Resolved" <?= $bug['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                </select>
                                <input type="text" class="commentInput mr-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" data-bug-id="<?= $bug['id'] ?>" placeholder="Add comment">
                                <button class="updateBug bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" data-bug-id="<?= $bug['id'] ?>">Update</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif;?>
                </tbody>
            </table>
        </div>
    </div>

    <script type="text/javascript">
        // Pusher setup for real-time updates
        var pusher = new Pusher('{REPLACE_THE_KEY}', {
            cluster: 'eu'
        });
        var channel = pusher.subscribe('bug-channel');
        channel.bind('new-bug', function(data) {
            var table = document.querySelector('#bugTable tbody');
            var row = table.insertRow(0);
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${data.id}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${data.title}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-${data.urgency == 'High' ? 'red' : (data.urgency == 'Medium' ? 'yellow' : 'green')}-100 text-${data.urgency == 'High' ? 'red' : (data.urgency == 'Medium' ? 'yellow' : 'green')}-800">
                        ${data.urgency}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${data.status}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                    <select class="statusSelect mr-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" data-bug-id="${data.id}">
                        <option value="New" selected>New</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Resolved">Resolved</option>
                    </select>
                    <input type="text" class="commentInput mr-2 rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" data-bug-id="${data.id}" placeholder="Add comment">
                    <button class="updateBug bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded" data-bug-id="${data.id}">Update</button>
                </td>
            `;
        });

        // Event listeners for updating bugs
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('updateBug')) {
                var bugId = event.target.dataset.bugId;
                var status = document.querySelector(`.statusSelect[data-bug-id="${bugId}"]`).value;
                var comment = document.querySelector(`.commentInput[data-bug-id="${bugId}"]`).value;
                fetch('/api/update_bug.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        bug_id: bugId,
                        status: status,
                        comment: comment
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Bug updated successfully!');
                    }
                });
            }
        });
    </script>
</body>
</html>