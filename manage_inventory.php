<?php
session_start();
include 'db_connection.php';
require_once 'logger.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$searchQuery = isset($_GET['query']) ? trim($_GET['query']) : '';

// Fetch books based on the search query or fetch all if no query
if ($searchQuery) {
    $sql = "SELECT * FROM books WHERE title LIKE ? OR author LIKE ? OR genre LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
} else {
    $sql = "SELECT * FROM books";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Inventory</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background-color: #1e1e1e;
    }

    th, td {
      padding: 12px;
      border: 1px solid #444;
      text-align: left;
    }

    th {
      background-color: #2a2a2a;
    }

    tr:nth-child(even) {
      background-color: #2d2d2d;
    }

    .btn {
      padding: 12px 20px;
      font-size: 16px;
      background-color: #5a6e8c;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      text-decoration: none;
      transition: background-color 0.2s ease-in-out;
      display: inline-block;
      margin-right: 10px;
    }

    .btn:hover {
      background-color: #4a5d78;
    }

    .btn-delete {
      background-color: #5c2e2e;
      border: 1px solid #6c3c3c;
    }

    .btn-delete:hover {
      background-color: #783535;
      border-color: #8b4444;
    }

    input[type="text"],
    input[type="number"],
    textarea {
      width: 100%;
      padding: 0.6rem;
      border-radius: 6px;
      border: 1px solid #ccc;
      background-color: #1e1e1e;
      color: #eee;
      margin-bottom: 1rem;
    }

    input[type="submit"] {
      background-color: #5a6e8c;
      color: #fff;
      padding: 12px 20px;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
      margin-top: 0.5rem;
    }

    input[type="submit"]:hover {
      background-color: #4a5d78;
    }

    .search-bar {
      margin-bottom: 20px;
    }

    button {
      padding: 12px 20px;
      font-size: 16px;
      background-color: #5a6e8c;
      color: #fff;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
    }

    button:hover {
      background-color: #4a5d78;
    }

  </style>
</head>
<body>

  <?php include 'admin_nav.php'; ?>

  <div class="container">
    <header>
      <h1>Manage Inventory</h1>
        <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
        <p style="color: lightgreen; background-color: #2a2a2a; padding: 10px 15px; border-radius: 6px; margin-top: 1rem;">
            ✅ Book added successfully!
        </p>
	<?php endif; ?>

	<?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
  	<p style="color: lightblue; background-color: #2a2a2a; padding: 10px 15px; border-radius: 6px; margin-top: 1rem;">
   	 🔄 Book updated successfully!
 	 </p>
	<?php endif; ?>

	<?php if (isset($_GET['success']) && $_GET['success'] === 'book_deleted'): ?>
    	<p style="color: lightblue; background-color: #2a2a2a; padding: 10px 15px; border-radius: 6px; margin-top: 1rem;">
        ✅ Book deleted successfully!
    	</p>
	<?php endif; ?>

    </header>

    <section>
      <h2>Add New Book</h2>
      <form method="post" action="add_book.php">
        <input type="text" name="title" placeholder="Title" required>
        <input type="text" name="author" placeholder="Author" required>
        <input type="text" name="genre" placeholder="Genre" required>
        <input type="submit" value="Add Book">
      </form>
    </section>

    <section class="search-bar">
      <h2>Search Inventory</h2>
      <form id="searchForm">
        <input type="text" id="searchInput" name="query" placeholder="Search by title, author, or genre" value="<?= htmlspecialchars($searchQuery) ?>" required>
        <button type="submit">Search</button>
        <button type="button" id="clearSearch">Show All</button>
      </form>
    </section>

    <section>
      <h2>Inventory List</h2>
      <div id="searchResults">
        <?php if ($result->num_rows > 0): ?>
          <table>
            <tr>
              <th>ID</th><th>Title</th><th>Author</th><th>Genre</th><th>Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= $row['book_id'] ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['author']) ?></td>
                <td><?= htmlspecialchars($row['genre']) ?></td>
                <td>
                  <a href="update_book.php?id=<?= $row['book_id'] ?>" class="btn">Update</a>
                  <a href="delete_book.php?id=<?= $row['book_id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this book?');">Delete</a>
                </td>
              </tr>
            <?php endwhile; ?>
          </table>
        <?php else: ?>
          <p>No books found in the inventory.</p>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <script>
    // Submit search using AJAX (no page reload)
    document.getElementById("searchForm").addEventListener("submit", function(e) {
      e.preventDefault(); // Prevent full reload

      const query = document.getElementById("searchInput").value;
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "manage_inventory.php?query=" + encodeURIComponent(query), true);
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

      xhr.onload = function () {
        if (xhr.status === 200) {
          const parser = new DOMParser();
          const doc = parser.parseFromString(xhr.responseText, "text/html");
          const newResults = doc.getElementById("searchResults");
          document.getElementById("searchResults").innerHTML = newResults.innerHTML;
        }
      };

      xhr.send();
    });

    // Clear search results (Show all books) using AJAX (no page reload)
    document.getElementById("clearSearch").addEventListener("click", function() {
      document.getElementById("searchInput").value = ''; // Clear search input

      // Make an AJAX request to reload all books
      const xhr = new XMLHttpRequest();
      xhr.open("GET", "manage_inventory.php", true); // No query parameter to show all books
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

      xhr.onload = function () {
        if (xhr.status === 200) {
          const parser = new DOMParser();
          const doc = parser.parseFromString(xhr.responseText, "text/html");
          const newResults = doc.getElementById("searchResults");
          document.getElementById("searchResults").innerHTML = newResults.innerHTML;
        }
      };

      xhr.send();
    });
  </script>

</body>
</html>
