<?php
session_start();
include 'db_connection.php';
require_once 'logger.php';

// Ensure the user is logged in as an admin
if (isset($_SESSION['admin_id'])) {
    $admin_id = intval($_SESSION['admin_id']);

    // Log the report view
    $logDetails = [
        'admin_id'  => $admin_id,
        'action'    => 'view_reports',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    logAction('view_reports', $logDetails);

    // Fetch most borrowed books
    $chart_labels = [];
    $chart_data = [];

    $sql = "SELECT books.title, COUNT(*) AS borrow_count 
            FROM BorrowedBooks 
            JOIN books ON BorrowedBooks.book_id = books.book_id 
            GROUP BY books.title 
            ORDER BY borrow_count DESC 
            LIMIT 5";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $chart_labels[] = $row['title'];
            $chart_data[] = $row['borrow_count'];
        }
    }

    // Fetch overdue books
    $overdue_sql = "SELECT users.username, books.title, BorrowedBooks.due_date 
                    FROM BorrowedBooks 
                    JOIN books ON BorrowedBooks.book_id = books.book_id 
                    JOIN users ON BorrowedBooks.user_id = users.user_id 
                    WHERE books.status = 'borrowed' AND BorrowedBooks.due_date < CURDATE()";
    $overdue_result = $conn->query($overdue_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="stylesheet" href="/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        canvas {
            background-color: #2d2d2d;
            border-radius: 10px;
            margin-bottom: 40px;
        }
    </style>
</head>
<body class="admin-body">

    <!-- Include the Admin Navbar -->
    <?php include 'admin_nav.php'; ?>

    <div class="container">
        <h1 class="page-title">Reports</h1>

        <!-- Most Borrowed Books Visualization -->
        <h2>Most Borrowed Books</h2>
        <canvas id="borrowedBooksChart" width="400" height="200"></canvas>

        <!-- Backup Table View -->
        <?php if (!empty($chart_labels)): ?>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Times Borrowed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 0; $i < count($chart_labels); $i++): ?>
                        <tr>
                            <td><?= htmlspecialchars($chart_labels[$i], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($chart_data[$i], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No data available.</p>
        <?php endif; ?>

        <!-- Overdue Books Report -->
        <h2>Overdue Books</h2>
        <?php if ($overdue_result && $overdue_result->num_rows > 0): ?>
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
                            <td><?= htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8') ?></td>
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

    <!-- Chart JS Script -->
    <script>
        const ctx = document.getElementById('borrowedBooksChart').getContext('2d');
        const borrowedBooksChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Times Borrowed',
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    borderRadius: 5,
                }]
            },
            options: {
                plugins: {
                    legend: {
                        labels: {
                            color: '#d4d4d4'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#d4d4d4'
                        },
                        grid: {
                            color: '#444'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#d4d4d4'
                        },
                        grid: {
                            color: '#444'
                        }
                    }
                }
            }
        });
    </script>

</body>
</html>

<?php
} else {
    echo "<p class='error-text'>Access denied. Admins only.</p>";
}
$conn->close();
?>

