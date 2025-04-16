<!-- nav.php -->
<nav class="nav-links">
  <a href="dashboard.php">Dashboard</a>
  <a href="borrow_books.php">Borrow Books</a>
  <a href="return_books.php">Return Books</a>
  <a href="search_books.php">Search Books</a>
  <a href="notifications.php" class="notifications-link">
    View Notifications
    <?php if ($unreadCount > 0): ?>
      <span class="notif-badge"><?= $unreadCount ?></span>
    <?php endif; ?>
  </a>
  <a href="logout.php" class="logout">Logout</a>
</nav>

