<?php
session_start();
include 'db_connection.php';
require_once 'logger.php'; // Include the logging function

// Ensure the user is logged in as an admin
if (isset($_SESSION['admin_id'])) {
    // Sanitize the admin ID (cast to integer)
    $admin_id = intval($_SESSION['admin_id']);
    
    // Log that the reports page was viewed
    $logDetails = [
        'admin_id'  => $admin_id,
        'action'    => 'view_reports',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    logAction('view_reports', $logDetails);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #1e1e1e;
            color: #d4d4d4;
            margin: 0;
            padding: 20px;
        }

        h1, h2 {
            color: #ffffff;
        }

        .actions {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .actions a {
            background-color: #2d2d2d;
            color: #ffffff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .actions a:hover {
            background-color: #3a3d41;
        }

        .actions a.logout {
            background-color: #b33a3a;
        }

        .actions a.logout:hover {
            background-color: #d64545;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid #444;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #2a2a2a;
            color: #fff;
        }

        tr:nth-child(even) {
            background-color: #2d2d2d;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-update {
            background-color: #3a3d41;
            color: #fff;
        }

        .btn-update:hover {
            background-color: #505357;
        }

        .btn-delete {
            background-color: #b33a3a;
            color: #fff;
        }

        .btn-delete:hover {
            background-color: #d64545;
        }
    </style>
</head>
<body class="admin-body">

    <!-- Include the Admin Navbar -->
    <?php include 'admin_nav.php'; ?>

    <div class="container">
        <h1 class="page-title">Reports</h1>

        <!-- Report: Most Borrowed Books -->
        <h2>Most Borrowed Books</h2>
        <?php
        $sql = "SELECT books.title, COUNT(BorrowedBooks.book_id) AS borrow_count 
                FROM BorrowedBooks 
                JOIN books ON BorrowedBooks.book_id = books.book_id 
                GROUP BY BorrowedBooks.book_id 
                ORDER BY borrow_count DESC 
                LIMIT 5";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Times Borrowed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['borrow_count'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No data available.</p>
        <?php endif; ?>

        <!-- Report: Overdue Books -->
        <h2>Overdue Books</h2>
        <?php
        $overdue_sql = "SELECT users.full_name, books.title, BorrowedBooks.due_date 
                        FROM BorrowedBooks 
                        JOIN books ON BorrowedBooks.book_id = books.book_id 
                        JOIN users ON BorrowedBooks.user_id = users.user_id 
                        WHERE books.status = 'borrowed' AND BorrowedBooks.due_date < CURDATE()";
        $overdue_result = $conn->query($overdue_sql);

        if ($overdue_result && $overdue_result->num_rows > 0): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Title</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $overdue_result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($row['due_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No overdue books at this time.</p>
        <?php endif; ?>

    </div>

</body>
</html>

<?php
} else {
    echo "<p class='error-text'>Access denied. Admins only.</p>";
}

$conn->close();
?>

