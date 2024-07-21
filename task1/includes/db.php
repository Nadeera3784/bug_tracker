<?php
function getDbConnection() {
    $db = new SQLite3(DB_PATH);
    $db->enableExceptions(true);
    return $db;
}

function checkTableExists($tableName) {
    $db = getDbConnection();
    $query = "SELECT name FROM sqlite_master WHERE type='table' AND name=:tableName";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':tableName', $tableName, SQLITE3_TEXT);
    $result = $stmt->execute();
    $exists = $result->fetchArray(SQLITE3_ASSOC);
    
    if (!$exists) {
        switch ($tableName) {
            case 'bugs':
                migrateBugsTable($db);
                break;
            case 'comments':
                migrateCommentsTable($db);
                break;
            case 'users':
                migrateUsersTable($db);
                break;
            default:
                throw new Exception("Unknown table: $tableName");
        }
    }
    
    $db->close();
}

function executeQuery($db, $sql) {
    $result = $db->exec($sql);
    if ($result === false) {
        throw new Exception("Error executing SQL: " . $db->lastErrorMsg());
    }
}

function migrateBugsTable($db) {
    $sql = "
    CREATE TABLE IF NOT EXISTS bugs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        description TEXT,
        urgency TEXT,
        submitted_by TEXT,
        status TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($db, $sql);
}

function migrateCommentsTable($db) {
    $sql = "
    CREATE TABLE IF NOT EXISTS comments (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bug_id INTEGER,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (bug_id) REFERENCES bugs(id)
    )";
    executeQuery($db, $sql);
    
    $indexSql = "CREATE INDEX IF NOT EXISTS idx_comments_bug_id ON comments(bug_id)";
    executeQuery($db, $indexSql);
}

function migrateUsersTable($db) {
    $sql = "
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        github_id TEXT UNIQUE NOT NULL,
        username TEXT NOT NULL,
        access_token TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    executeQuery($db, $sql);
    
    $indexSql = "CREATE INDEX IF NOT EXISTS idx_users_github_id ON users(github_id)";
    executeQuery($db, $indexSql);
    
    $triggerSql = "
    CREATE TRIGGER IF NOT EXISTS update_users_timestamp 
    AFTER UPDATE ON users
    BEGIN
        UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = NEW.id;
    END";
    executeQuery($db, $triggerSql);
}

function getBugList($db){
    checkTableExists('bugs');
    return $db->query('SELECT * FROM bugs ORDER BY created_at DESC');
}

function getBugInfo($db, $bug_id, $submitter_ip) {
    checkTableExists('bugs');
    $stmt = $db->prepare('SELECT status, last_updated FROM bugs WHERE id = :id AND submitted_by = :submitter_ip');
    $stmt->bindValue(':id', $bug_id, SQLITE3_INTEGER);
    $stmt->bindValue(':submitter_ip', $submitter_ip, SQLITE3_TEXT);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}


function getLatestComment($db, $bug_id) {
    checkTableExists('comments');
    $stmt = $db->prepare('SELECT comment, created_at FROM comments WHERE bug_id = :id ORDER BY created_at DESC LIMIT 1');
    $stmt->bindValue(':id', $bug_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    return $result->fetchArray(SQLITE3_ASSOC);
}

function createBug($db, $data){
    checkTableExists('bugs');
    $stmt = $db->prepare('INSERT INTO bugs (title, description, urgency, submitted_by) VALUES (:title, :description, :urgency, :submitted_by)');
    $stmt->bindValue(':title', $data['title'], SQLITE3_TEXT);
    $stmt->bindValue(':description', $data['description'], SQLITE3_TEXT);
    $stmt->bindValue(':urgency', $data['urgency'], SQLITE3_TEXT);
    $stmt->bindValue(':submitted_by', $_SERVER['REMOTE_ADDR'], SQLITE3_TEXT);
    return $stmt->execute();
}

function updateBug($db, $data){
    checkTableExists('bugs');
    $stmt = $db->prepare('UPDATE bugs SET status = :status, last_updated = CURRENT_TIMESTAMP WHERE id = :id');
    $stmt->bindValue(':status', $data['status'], SQLITE3_TEXT);
    $stmt->bindValue(':id', $data['bug_id'], SQLITE3_INTEGER);
    return $stmt->execute();
}

function createComments($db, $data){
    checkTableExists('comments');
    $stmt = $db->prepare('INSERT INTO comments (bug_id, comment) VALUES (:bug_id, :comment)');
    $stmt->bindValue(':bug_id', $data['bug_id'], SQLITE3_INTEGER);
    $stmt->bindValue(':comment', $data['comment'], SQLITE3_TEXT);
    return $stmt->execute();
}

function createUser($db, $user_data, $access_token){
    $stmt = $db->prepare('INSERT OR REPLACE INTO users (github_id, username, access_token) VALUES (:github_id, :username, :access_token)');
    $stmt->bindValue(':github_id', $user_data['id'], SQLITE3_TEXT);
    $stmt->bindValue(':username', $user_data['login'], SQLITE3_TEXT);
    $stmt->bindValue(':access_token', $access_token, SQLITE3_TEXT);
    $stmt->execute();
}