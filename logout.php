<?php
session_start();
session_destroy();

// Xóa key trong localStorage bằng JavaScript
echo '<script>
    localStorage.removeItem("accessKey");
    window.location.href = "login.html";
</script>';
?>