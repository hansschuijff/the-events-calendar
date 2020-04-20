<?php
/**
 * View: Latest Past View
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/v2/latest-past.php
 *
 * See more documentation about our views templating system.
 *
 * @link {INSERT_ARTCILE_LINK_HERE}
 *
 * @version TBD
 *
 * @var array $events The array containing the events.
 */
?>
<div class="tribe-events-calendar-latest-past">

	<?php $this->template( 'latest-past/heading' ); ?>

	<?php foreach ( $events as $event ) : ?>
		<?php $this->setup_postdata( $event ); ?>

		<?php $this->template( 'latest-past/event', [ 'event' => $event ] ); ?>

	<?php endforeach; ?>

</div>