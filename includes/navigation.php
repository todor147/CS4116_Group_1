            <!-- Add Customer Insights to the dropdown if user is logged in -->
            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-chat-dots"></i> Messages
                        <?php if (isset($unread_count) && $unread_count > 0): ?>
                            <span class="badge rounded-pill bg-danger"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="messagesDropdown">
                        <li><a class="dropdown-item" href="messages.php"><i class="bi bi-envelope"></i> Coach Messages</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="manage_insight_requests.php"><i class="bi bi-people"></i> Insight Requests</a></li>
                    </ul>
                </li>
            <?php endif; ?>

            <!-- Other navigation items --> 