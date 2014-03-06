<?php
// DO NOT EDIT! Generated by Protobuf-PHP protoc plugin 0.9.4
// Source: User.proto
//   Date: 2014-03-05 17:53:46

namespace SolasMatch\Common\Protobufs\Models {

  class User extends \DrSlump\Protobuf\Message {

    /**  @var int */
    public $id = null;
    
    /**  @var string */
    public $display_name = null;
    
    /**  @var string */
    public $email = null;
    
    /**  @var string */
    public $password = null;
    
    /**  @var string */
    public $biography = null;
    
    /**  @var string */
    public $nonce = null;
    
    /**  @var string */
    public $created_time = null;
    
    /**  @var \SolasMatch\Common\Protobufs\Models\Locale */
    public $nativeLocale = null;
    
    /**  @var \SolasMatch\Common\Protobufs\Models\Locale[]  */
    public $secondaryLocales = array();
    

    /** @var \Closure[] */
    protected static $__extensions = array();

    public static function descriptor()
    {
      $descriptor = new \DrSlump\Protobuf\Descriptor(__CLASS__, 'SolasMatch.Common.Protobufs.Models.User');

      // OPTIONAL INT32 id = 1
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 1;
      $f->name      = "id";
      $f->type      = \DrSlump\Protobuf::TYPE_INT32;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL STRING display_name = 2
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 2;
      $f->name      = "display_name";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL STRING email = 3
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 3;
      $f->name      = "email";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL STRING password = 4
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 4;
      $f->name      = "password";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL STRING biography = 5
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 5;
      $f->name      = "biography";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL STRING nonce = 6
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 6;
      $f->name      = "nonce";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL STRING created_time = 7
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 7;
      $f->name      = "created_time";
      $f->type      = \DrSlump\Protobuf::TYPE_STRING;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $descriptor->addField($f);

      // OPTIONAL MESSAGE nativeLocale = 8
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 8;
      $f->name      = "nativeLocale";
      $f->type      = \DrSlump\Protobuf::TYPE_MESSAGE;
      $f->rule      = \DrSlump\Protobuf::RULE_OPTIONAL;
      $f->reference = '\SolasMatch\Common\Protobufs\Models\Locale';
      $descriptor->addField($f);

      // REPEATED MESSAGE secondaryLocales = 9
      $f = new \DrSlump\Protobuf\Field();
      $f->number    = 9;
      $f->name      = "secondaryLocales";
      $f->type      = \DrSlump\Protobuf::TYPE_MESSAGE;
      $f->rule      = \DrSlump\Protobuf::RULE_REPEATED;
      $f->reference = '\SolasMatch\Common\Protobufs\Models\Locale';
      $descriptor->addField($f);

      foreach (self::$__extensions as $cb) {
        $descriptor->addField($cb(), true);
      }

      return $descriptor;
    }

    /**
     * Check if <id> has a value
     *
     * @return boolean
     */
    public function hasId(){
      return $this->_has(1);
    }
    
    /**
     * Clear <id> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearId(){
      return $this->_clear(1);
    }
    
    /**
     * Get <id> value
     *
     * @return int
     */
    public function getId(){
      return $this->_get(1);
    }
    
    /**
     * Set <id> value
     *
     * @param int $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setId( $value){
      return $this->_set(1, $value);
    }
    
    /**
     * Check if <display_name> has a value
     *
     * @return boolean
     */
    public function hasDisplayName(){
      return $this->_has(2);
    }
    
    /**
     * Clear <display_name> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearDisplayName(){
      return $this->_clear(2);
    }
    
    /**
     * Get <display_name> value
     *
     * @return string
     */
    public function getDisplayName(){
      return $this->_get(2);
    }
    
    /**
     * Set <display_name> value
     *
     * @param string $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setDisplayName( $value){
      return $this->_set(2, $value);
    }
    
    /**
     * Check if <email> has a value
     *
     * @return boolean
     */
    public function hasEmail(){
      return $this->_has(3);
    }
    
    /**
     * Clear <email> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearEmail(){
      return $this->_clear(3);
    }
    
    /**
     * Get <email> value
     *
     * @return string
     */
    public function getEmail(){
      return $this->_get(3);
    }
    
    /**
     * Set <email> value
     *
     * @param string $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setEmail( $value){
      return $this->_set(3, $value);
    }
    
    /**
     * Check if <password> has a value
     *
     * @return boolean
     */
    public function hasPassword(){
      return $this->_has(4);
    }
    
    /**
     * Clear <password> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearPassword(){
      return $this->_clear(4);
    }
    
    /**
     * Get <password> value
     *
     * @return string
     */
    public function getPassword(){
      return $this->_get(4);
    }
    
    /**
     * Set <password> value
     *
     * @param string $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setPassword( $value){
      return $this->_set(4, $value);
    }
    
    /**
     * Check if <biography> has a value
     *
     * @return boolean
     */
    public function hasBiography(){
      return $this->_has(5);
    }
    
    /**
     * Clear <biography> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearBiography(){
      return $this->_clear(5);
    }
    
    /**
     * Get <biography> value
     *
     * @return string
     */
    public function getBiography(){
      return $this->_get(5);
    }
    
    /**
     * Set <biography> value
     *
     * @param string $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setBiography( $value){
      return $this->_set(5, $value);
    }
    
    /**
     * Check if <nonce> has a value
     *
     * @return boolean
     */
    public function hasNonce(){
      return $this->_has(6);
    }
    
    /**
     * Clear <nonce> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearNonce(){
      return $this->_clear(6);
    }
    
    /**
     * Get <nonce> value
     *
     * @return string
     */
    public function getNonce(){
      return $this->_get(6);
    }
    
    /**
     * Set <nonce> value
     *
     * @param string $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setNonce( $value){
      return $this->_set(6, $value);
    }
    
    /**
     * Check if <created_time> has a value
     *
     * @return boolean
     */
    public function hasCreatedTime(){
      return $this->_has(7);
    }
    
    /**
     * Clear <created_time> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearCreatedTime(){
      return $this->_clear(7);
    }
    
    /**
     * Get <created_time> value
     *
     * @return string
     */
    public function getCreatedTime(){
      return $this->_get(7);
    }
    
    /**
     * Set <created_time> value
     *
     * @param string $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setCreatedTime( $value){
      return $this->_set(7, $value);
    }
    
    /**
     * Check if <nativeLocale> has a value
     *
     * @return boolean
     */
    public function hasNativeLocale(){
      return $this->_has(8);
    }
    
    /**
     * Clear <nativeLocale> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearNativeLocale(){
      return $this->_clear(8);
    }
    
    /**
     * Get <nativeLocale> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\Locale
     */
    public function getNativeLocale(){
      return $this->_get(8);
    }
    
    /**
     * Set <nativeLocale> value
     *
     * @param \SolasMatch\Common\Protobufs\Models\Locale $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setNativeLocale(\SolasMatch\Common\Protobufs\Models\Locale $value){
      return $this->_set(8, $value);
    }
    
    /**
     * Check if <secondaryLocales> has a value
     *
     * @return boolean
     */
    public function hasSecondaryLocales(){
      return $this->_has(9);
    }
    
    /**
     * Clear <secondaryLocales> value
     *
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function clearSecondaryLocales(){
      return $this->_clear(9);
    }
    
    /**
     * Get <secondaryLocales> value
     *
     * @param int $idx
     * @return \SolasMatch\Common\Protobufs\Models\Locale
     */
    public function getSecondaryLocales($idx = NULL){
      return $this->_get(9, $idx);
    }
    
    /**
     * Set <secondaryLocales> value
     *
     * @param \SolasMatch\Common\Protobufs\Models\Locale $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function setSecondaryLocales(\SolasMatch\Common\Protobufs\Models\Locale $value, $idx = NULL){
      return $this->_set(9, $value, $idx);
    }
    
    /**
     * Get all elements of <secondaryLocales>
     *
     * @return \SolasMatch\Common\Protobufs\Models\Locale[]
     */
    public function getSecondaryLocalesList(){
     return $this->_get(9);
    }
    
    /**
     * Add a new element to <secondaryLocales>
     *
     * @param \SolasMatch\Common\Protobufs\Models\Locale $value
     * @return \SolasMatch\Common\Protobufs\Models\User
     */
    public function addSecondaryLocales(\SolasMatch\Common\Protobufs\Models\Locale $value){
     return $this->_add(9, $value);
    }
  }
}

