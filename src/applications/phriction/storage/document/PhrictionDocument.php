<?php

/*
 * Copyright 2012 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * @group phriction
 */
class PhrictionDocument extends PhrictionDAO {

  protected $id;
  protected $phid;
  protected $slug;
  protected $depth;
  protected $contentID;
  protected $status;

  private $contentObject;

  public function getConfiguration() {
    return array(
      self::CONFIG_AUX_PHID   => true,
      self::CONFIG_TIMESTAMPS => false,
    ) + parent::getConfiguration();
  }

  public function generatePHID() {
    return PhabricatorPHID::generateNewPHID(
      PhabricatorPHIDConstants::PHID_TYPE_WIKI);
  }

  public static function getSlugURI($slug, $type = 'document') {
    static $types = array(
      'document'  => '/w/',
      'history'   => '/phriction/history/',
    );

    if (empty($types[$type])) {
      throw new Exception("Unknown URI type '{$type}'!");
    }

    $prefix = $types[$type];

    if ($slug == '/') {
      return $prefix;
    } else {
      return $prefix.$slug;
    }
  }

  public static function normalizeSlug($slug) {

    // TODO: We need to deal with unicode at some point, this is just a very
    // basic proof-of-concept implementation.

    $slug = strtolower($slug);
    $slug = preg_replace('@/+@', '/', $slug);
    $slug = trim($slug, '/');
    $slug = preg_replace('@[^a-z0-9/]+@', '_', $slug);
    $slug = trim($slug, '_');

    return $slug.'/';
  }

  public static function getDefaultSlugTitle($slug) {
    $parts = explode('/', trim($slug, '/'));
    $default_title = end($parts);
    $default_title = str_replace('_', ' ', $default_title);
    $default_title = ucwords($default_title);
    $default_title = nonempty($default_title, 'Untitled Document');
    return $default_title;
  }

  public static function getSlugAncestry($slug) {
    $slug = self::normalizeSlug($slug);

    if ($slug == '/') {
      return array();
    }

    $ancestors = array(
      '/',
    );

    $slug = explode('/', $slug);
    array_pop($slug);
    array_pop($slug);

    $accumulate = '';
    foreach ($slug as $part) {
      $accumulate .= $part.'/';
      $ancestors[] = $accumulate;
    }

    return $ancestors;
  }

  public static function getSlugDepth($slug) {
    $slug = self::normalizeSlug($slug);
    if ($slug == '/') {
      return 0;
    } else {
      return substr_count($slug, '/');
    }
  }

  public function setSlug($slug) {
    $this->slug   = self::normalizeSlug($slug);
    $this->depth  = self::getSlugDepth($slug);
    return $this;
  }

  public function attachContent(PhrictionContent $content) {
    $this->contentObject = $content;
    return $this;
  }

  public function getContent() {
    if (!$this->contentObject) {
      throw new Exception("Attach content with attachContent() first.");
    }
    return $this->contentObject;
  }

  public static function isProjectSlug($slug) {
    $slug = self::normalizeSlug($slug);
    $prefix = 'projects/';
    if ($slug == $prefix) {
      // The 'projects/' document is not itself a project slug.
      return false;
    }
    return !strncmp($slug, $prefix, strlen($prefix));
  }

  public static function getProjectSlugIdentifier($slug) {
    if (!self::isProjectSlug($slug)) {
      throw new Exception("Slug '{$slug}' is not a project slug!");
    }

    $slug = self::normalizeSlug($slug);
    $parts = explode('/', $slug);
    return $parts[1].'/';
  }

}
