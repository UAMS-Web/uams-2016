<form role="search" method="get" id="searchform" class="searchform" action="<?php echo esc_url( set_url_scheme( home_url( '/' ) ) ); ?>">
	<fieldset>
		<div>
			<label class="screen-reader-text" for="s">Search for:</label>
			<input type="text" value="" name="s" id="s" placeholder="Search for:" autocomplete="off">
			<input type="submit" id="searchsubmit" value="Search">
		</div>
	</fieldset>
</form>
