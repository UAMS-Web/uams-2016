    <div role="contentinfo" class="uams-footer">

        <a href="http://www.uams.edu" class="footer-wordmark">University of Arkansas for Medical Sciences</a>

        <h4>Connect with us:</h4>

        <nav aria-label="social networking">
            <ul class="footer-social">
                <li><a class="facebook" href="http://facebook.com/uamshealth">Facebook</a></li>
                <li><a class="twitter" href="http://twitter.com/uamshealth">Twitter</a></li>
                <li><a class="instagram" href="http://instagram.com/uamshealth">Instagram</a></li>
                <li><a class="youtube" href="https://www.youtube.com/user/UAMSHealth">YouTube</a></li>
                <li><a class="linkedin" href="https://www.linkedin.com/company/uams">LinkedIn</a></li>
                <li><a class="pinterest" href="http://pinterest.com/uamshealth">Pinterest</a></li>
            </ul>
        </nav>

        <nav aria-label="footer links">
            <ul class="footer-links">
                <li><a href="https://uamshealth.com/disclaimer/">Disclaimer</a></li>
                <li><a href="https://jobs.uams.edu/">Jobs</a></li>
                <li><a href="https://uamshealth.com/privacy/#legal">Copyright Statement</a></li>
                <li><a href="https://uamshealth.com/privacy/">Privacy</a></li>
                <li><a href="https://uamshealth.com/terms-of-use/">Terms</a></li>
            </ul>
        </nav>

        <p>&copy; <?php echo date("Y"); ?> University of Arkansas for Medical Sciences  |  Little Rock, AR</p>

    </div>

    </div><!-- #uams-container-inner -->
    </div><!-- #uams-container -->

<?php wp_footer(); ?>

<?php
    if ( get_post_meta( get_the_ID() , 'custom_footer_script' , 'true' ) )
		echo get_post_meta( get_the_ID() , 'custom_footer_script' , 'true' );
    ?>

</body>
</html>
