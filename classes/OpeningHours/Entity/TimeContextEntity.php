<?php

namespace OpeningHours\Entity;
defined( 'ABSPATH' ) || exit;

interface TimeContextEntity {
  /**
   * Checks whether the entity happens on a specific date
   * @param     \DateTime   $date   The reference date
   * @return    bool                Whether the entity happens on that specific date
   */
  public function happensOnDate(\DateTime $date);
}
