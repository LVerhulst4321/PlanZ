<?php
    function staff_footer() {
?>
<footer class="clearboth">
<hr>
<p> 
If you would like assistance using this tool, please contact 
<?php
    echo "<a href=\"mailto:".PROGRAM_EMAIL."\">".PROGRAM_EMAIL."</a> ";
    include ('google_analytics.php');
?>
</p>
</footer>
</body>
</html>

<?php } ?>
