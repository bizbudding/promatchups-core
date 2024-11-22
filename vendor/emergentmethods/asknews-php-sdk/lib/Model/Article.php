<?php
/**
 * Article
 *
 * PHP version 7.4
 *
 * @category Class
 * @package  AskNews
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 */

/**
 * AskNews API
 *
 * AskNews API
 *
 * The version of the OpenAPI document: 0.14.8
 * Generated by: https://openapi-generator.tech
 * Generator version: 7.5.0
 */

/**
 * NOTE: This class is auto generated by OpenAPI Generator (https://openapi-generator.tech).
 * https://openapi-generator.tech
 * Do not edit the class manually.
 */

namespace AskNews\Model;

use \ArrayAccess;
use \AskNews\ObjectSerializer;

/**
 * Article Class Doc Comment
 *
 * @category Class
 * @package  AskNews
 * @author   OpenAPI Generator team
 * @link     https://openapi-generator.tech
 * @implements \ArrayAccess<string, mixed>
 */
class Article implements ModelInterface, ArrayAccess, \JsonSerializable
{
    public const DISCRIMINATOR = null;

    /**
      * The original name of the model.
      *
      * @var string
      */
    protected static $openAPIModelName = 'Article';

    /**
      * Array of property to type mappings. Used for (de)serialization
      *
      * @var string[]
      */
    protected static $openAPITypes = [
        'article_url' => 'string',
        'article_id' => 'string',
        'classification' => '\AskNews\Model\Classification',
        'country' => 'string',
        'source_id' => 'string',
        'page_rank' => 'int',
        'domain_url' => 'string',
        'eng_title' => 'string',
        'entities' => '\AskNews\Model\Entities',
        'image_url' => 'string',
        'keywords' => 'string[]',
        'language' => 'string',
        'pub_date' => '\DateTime',
        'summary' => 'string',
        'title' => 'string',
        'sentiment' => 'int',
        'centroid_distance' => 'float',
        'cluster_probability' => 'float',
        'markdown_citation' => 'string',
        'provocative' => 'string',
        'reporting_voice' => '\AskNews\Model\ReportingVoice1',
        'entity_relation_graph' => '\AskNews\Model\AsknewsApiSchemaV1CommonGraphRelationships',
        'geo_coordinates' => 'array<string,\AskNews\Model\GeoCoordinate>'
    ];

    /**
      * Array of property to format mappings. Used for (de)serialization
      *
      * @var string[]
      * @phpstan-var array<string, string|null>
      * @psalm-var array<string, string|null>
      */
    protected static $openAPIFormats = [
        'article_url' => 'uri',
        'article_id' => 'uuid',
        'classification' => null,
        'country' => null,
        'source_id' => null,
        'page_rank' => null,
        'domain_url' => null,
        'eng_title' => null,
        'entities' => null,
        'image_url' => null,
        'keywords' => null,
        'language' => null,
        'pub_date' => 'date-time',
        'summary' => null,
        'title' => null,
        'sentiment' => null,
        'centroid_distance' => null,
        'cluster_probability' => null,
        'markdown_citation' => null,
        'provocative' => null,
        'reporting_voice' => null,
        'entity_relation_graph' => null,
        'geo_coordinates' => null
    ];

    /**
      * Array of nullable properties. Used for (de)serialization
      *
      * @var boolean[]
      */
    protected static array $openAPINullables = [
        'article_url' => false,
        'article_id' => false,
        'classification' => false,
        'country' => false,
        'source_id' => false,
        'page_rank' => false,
        'domain_url' => false,
        'eng_title' => false,
        'entities' => false,
        'image_url' => true,
        'keywords' => false,
        'language' => false,
        'pub_date' => false,
        'summary' => false,
        'title' => false,
        'sentiment' => false,
        'centroid_distance' => false,
        'cluster_probability' => false,
        'markdown_citation' => false,
        'provocative' => false,
        'reporting_voice' => false,
        'entity_relation_graph' => true,
        'geo_coordinates' => true
    ];

    /**
      * If a nullable field gets set to null, insert it here
      *
      * @var boolean[]
      */
    protected array $openAPINullablesSetToNull = [];

    /**
     * Array of property to type mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPITypes()
    {
        return self::$openAPITypes;
    }

    /**
     * Array of property to format mappings. Used for (de)serialization
     *
     * @return array
     */
    public static function openAPIFormats()
    {
        return self::$openAPIFormats;
    }

    /**
     * Array of nullable properties
     *
     * @return array
     */
    protected static function openAPINullables(): array
    {
        return self::$openAPINullables;
    }

    /**
     * Array of nullable field names deliberately set to null
     *
     * @return boolean[]
     */
    private function getOpenAPINullablesSetToNull(): array
    {
        return $this->openAPINullablesSetToNull;
    }

    /**
     * Setter - Array of nullable field names deliberately set to null
     *
     * @param boolean[] $openAPINullablesSetToNull
     */
    private function setOpenAPINullablesSetToNull(array $openAPINullablesSetToNull): void
    {
        $this->openAPINullablesSetToNull = $openAPINullablesSetToNull;
    }

    /**
     * Checks if a property is nullable
     *
     * @param string $property
     * @return bool
     */
    public static function isNullable(string $property): bool
    {
        return self::openAPINullables()[$property] ?? false;
    }

    /**
     * Checks if a nullable property is set to null.
     *
     * @param string $property
     * @return bool
     */
    public function isNullableSetToNull(string $property): bool
    {
        return in_array($property, $this->getOpenAPINullablesSetToNull(), true);
    }

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @var string[]
     */
    protected static $attributeMap = [
        'article_url' => 'article_url',
        'article_id' => 'article_id',
        'classification' => 'classification',
        'country' => 'country',
        'source_id' => 'source_id',
        'page_rank' => 'page_rank',
        'domain_url' => 'domain_url',
        'eng_title' => 'eng_title',
        'entities' => 'entities',
        'image_url' => 'image_url',
        'keywords' => 'keywords',
        'language' => 'language',
        'pub_date' => 'pub_date',
        'summary' => 'summary',
        'title' => 'title',
        'sentiment' => 'sentiment',
        'centroid_distance' => 'centroid_distance',
        'cluster_probability' => 'cluster_probability',
        'markdown_citation' => 'markdown_citation',
        'provocative' => 'provocative',
        'reporting_voice' => 'reporting_voice',
        'entity_relation_graph' => 'entity_relation_graph',
        'geo_coordinates' => 'geo_coordinates'
    ];

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @var string[]
     */
    protected static $setters = [
        'article_url' => 'setArticleUrl',
        'article_id' => 'setArticleId',
        'classification' => 'setClassification',
        'country' => 'setCountry',
        'source_id' => 'setSourceId',
        'page_rank' => 'setPageRank',
        'domain_url' => 'setDomainUrl',
        'eng_title' => 'setEngTitle',
        'entities' => 'setEntities',
        'image_url' => 'setImageUrl',
        'keywords' => 'setKeywords',
        'language' => 'setLanguage',
        'pub_date' => 'setPubDate',
        'summary' => 'setSummary',
        'title' => 'setTitle',
        'sentiment' => 'setSentiment',
        'centroid_distance' => 'setCentroidDistance',
        'cluster_probability' => 'setClusterProbability',
        'markdown_citation' => 'setMarkdownCitation',
        'provocative' => 'setProvocative',
        'reporting_voice' => 'setReportingVoice',
        'entity_relation_graph' => 'setEntityRelationGraph',
        'geo_coordinates' => 'setGeoCoordinates'
    ];

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @var string[]
     */
    protected static $getters = [
        'article_url' => 'getArticleUrl',
        'article_id' => 'getArticleId',
        'classification' => 'getClassification',
        'country' => 'getCountry',
        'source_id' => 'getSourceId',
        'page_rank' => 'getPageRank',
        'domain_url' => 'getDomainUrl',
        'eng_title' => 'getEngTitle',
        'entities' => 'getEntities',
        'image_url' => 'getImageUrl',
        'keywords' => 'getKeywords',
        'language' => 'getLanguage',
        'pub_date' => 'getPubDate',
        'summary' => 'getSummary',
        'title' => 'getTitle',
        'sentiment' => 'getSentiment',
        'centroid_distance' => 'getCentroidDistance',
        'cluster_probability' => 'getClusterProbability',
        'markdown_citation' => 'getMarkdownCitation',
        'provocative' => 'getProvocative',
        'reporting_voice' => 'getReportingVoice',
        'entity_relation_graph' => 'getEntityRelationGraph',
        'geo_coordinates' => 'getGeoCoordinates'
    ];

    /**
     * Array of attributes where the key is the local name,
     * and the value is the original name
     *
     * @return array
     */
    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    /**
     * Array of attributes to setter functions (for deserialization of responses)
     *
     * @return array
     */
    public static function setters()
    {
        return self::$setters;
    }

    /**
     * Array of attributes to getter functions (for serialization of requests)
     *
     * @return array
     */
    public static function getters()
    {
        return self::$getters;
    }

    /**
     * The original name of the model.
     *
     * @return string
     */
    public function getModelName()
    {
        return self::$openAPIModelName;
    }

    public const PROVOCATIVE_UNKNOWN = 'unknown';
    public const PROVOCATIVE_LOW = 'low';
    public const PROVOCATIVE_MEDIUM = 'medium';
    public const PROVOCATIVE_HIGH = 'high';

    /**
     * Gets allowable values of the enum
     *
     * @return string[]
     */
    public function getProvocativeAllowableValues()
    {
        return [
            self::PROVOCATIVE_UNKNOWN,
            self::PROVOCATIVE_LOW,
            self::PROVOCATIVE_MEDIUM,
            self::PROVOCATIVE_HIGH,
        ];
    }

    /**
     * Associative array for storing property values
     *
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     *
     * @param mixed[] $data Associated array of property values
     *                      initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->setIfExists('article_url', $data ?? [], null);
        $this->setIfExists('article_id', $data ?? [], null);
        $this->setIfExists('classification', $data ?? [], null);
        $this->setIfExists('country', $data ?? [], null);
        $this->setIfExists('source_id', $data ?? [], null);
        $this->setIfExists('page_rank', $data ?? [], null);
        $this->setIfExists('domain_url', $data ?? [], null);
        $this->setIfExists('eng_title', $data ?? [], null);
        $this->setIfExists('entities', $data ?? [], null);
        $this->setIfExists('image_url', $data ?? [], null);
        $this->setIfExists('keywords', $data ?? [], null);
        $this->setIfExists('language', $data ?? [], null);
        $this->setIfExists('pub_date', $data ?? [], null);
        $this->setIfExists('summary', $data ?? [], null);
        $this->setIfExists('title', $data ?? [], null);
        $this->setIfExists('sentiment', $data ?? [], null);
        $this->setIfExists('centroid_distance', $data ?? [], null);
        $this->setIfExists('cluster_probability', $data ?? [], null);
        $this->setIfExists('markdown_citation', $data ?? [], '');
        $this->setIfExists('provocative', $data ?? [], 'unknown');
        $this->setIfExists('reporting_voice', $data ?? [], null);
        $this->setIfExists('entity_relation_graph', $data ?? [], null);
        $this->setIfExists('geo_coordinates', $data ?? [], null);
    }

    /**
    * Sets $this->container[$variableName] to the given data or to the given default Value; if $variableName
    * is nullable and its value is set to null in the $fields array, then mark it as "set to null" in the
    * $this->openAPINullablesSetToNull array
    *
    * @param string $variableName
    * @param array  $fields
    * @param mixed  $defaultValue
    */
    private function setIfExists(string $variableName, array $fields, $defaultValue): void
    {
        if (self::isNullable($variableName) && array_key_exists($variableName, $fields) && is_null($fields[$variableName])) {
            $this->openAPINullablesSetToNull[] = $variableName;
        }

        $this->container[$variableName] = $fields[$variableName] ?? $defaultValue;
    }

    /**
     * Show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalidProperties = [];

        if ($this->container['article_url'] === null) {
            $invalidProperties[] = "'article_url' can't be null";
        }
        if ((mb_strlen($this->container['article_url']) > 2083)) {
            $invalidProperties[] = "invalid value for 'article_url', the character length must be smaller than or equal to 2083.";
        }

        if ((mb_strlen($this->container['article_url']) < 1)) {
            $invalidProperties[] = "invalid value for 'article_url', the character length must be bigger than or equal to 1.";
        }

        if ($this->container['article_id'] === null) {
            $invalidProperties[] = "'article_id' can't be null";
        }
        if ($this->container['classification'] === null) {
            $invalidProperties[] = "'classification' can't be null";
        }
        if ($this->container['country'] === null) {
            $invalidProperties[] = "'country' can't be null";
        }
        if ($this->container['source_id'] === null) {
            $invalidProperties[] = "'source_id' can't be null";
        }
        if ($this->container['page_rank'] === null) {
            $invalidProperties[] = "'page_rank' can't be null";
        }
        if ($this->container['domain_url'] === null) {
            $invalidProperties[] = "'domain_url' can't be null";
        }
        if ($this->container['eng_title'] === null) {
            $invalidProperties[] = "'eng_title' can't be null";
        }
        if ($this->container['entities'] === null) {
            $invalidProperties[] = "'entities' can't be null";
        }
        if ($this->container['keywords'] === null) {
            $invalidProperties[] = "'keywords' can't be null";
        }
        if ($this->container['language'] === null) {
            $invalidProperties[] = "'language' can't be null";
        }
        if ($this->container['pub_date'] === null) {
            $invalidProperties[] = "'pub_date' can't be null";
        }
        if ($this->container['summary'] === null) {
            $invalidProperties[] = "'summary' can't be null";
        }
        if ($this->container['title'] === null) {
            $invalidProperties[] = "'title' can't be null";
        }
        if ($this->container['sentiment'] === null) {
            $invalidProperties[] = "'sentiment' can't be null";
        }
        if ($this->container['centroid_distance'] === null) {
            $invalidProperties[] = "'centroid_distance' can't be null";
        }
        if ($this->container['cluster_probability'] === null) {
            $invalidProperties[] = "'cluster_probability' can't be null";
        }
        $allowedValues = $this->getProvocativeAllowableValues();
        if (!is_null($this->container['provocative']) && !in_array($this->container['provocative'], $allowedValues, true)) {
            $invalidProperties[] = sprintf(
                "invalid value '%s' for 'provocative', must be one of '%s'",
                $this->container['provocative'],
                implode("', '", $allowedValues)
            );
        }

        return $invalidProperties;
    }

    /**
     * Validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properties are valid
     */
    public function valid()
    {
        return count($this->listInvalidProperties()) === 0;
    }


    /**
     * Gets article_url
     *
     * @return string
     */
    public function getArticleUrl()
    {
        return $this->container['article_url'];
    }

    /**
     * Sets article_url
     *
     * @param string $article_url article_url
     *
     * @return self
     */
    public function setArticleUrl($article_url)
    {
        if (is_null($article_url)) {
            throw new \InvalidArgumentException('non-nullable article_url cannot be null');
        }
        if ((mb_strlen($article_url) > 2083)) {
            throw new \InvalidArgumentException('invalid length for $article_url when calling Article., must be smaller than or equal to 2083.');
        }
        if ((mb_strlen($article_url) < 1)) {
            throw new \InvalidArgumentException('invalid length for $article_url when calling Article., must be bigger than or equal to 1.');
        }

        $this->container['article_url'] = $article_url;

        return $this;
    }

    /**
     * Gets article_id
     *
     * @return string
     */
    public function getArticleId()
    {
        return $this->container['article_id'];
    }

    /**
     * Sets article_id
     *
     * @param string $article_id article_id
     *
     * @return self
     */
    public function setArticleId($article_id)
    {
        if (is_null($article_id)) {
            throw new \InvalidArgumentException('non-nullable article_id cannot be null');
        }
        $this->container['article_id'] = $article_id;

        return $this;
    }

    /**
     * Gets classification
     *
     * @return \AskNews\Model\Classification
     */
    public function getClassification()
    {
        return $this->container['classification'];
    }

    /**
     * Sets classification
     *
     * @param \AskNews\Model\Classification $classification classification
     *
     * @return self
     */
    public function setClassification($classification)
    {
        if (is_null($classification)) {
            throw new \InvalidArgumentException('non-nullable classification cannot be null');
        }
        $this->container['classification'] = $classification;

        return $this;
    }

    /**
     * Gets country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->container['country'];
    }

    /**
     * Sets country
     *
     * @param string $country country
     *
     * @return self
     */
    public function setCountry($country)
    {
        if (is_null($country)) {
            throw new \InvalidArgumentException('non-nullable country cannot be null');
        }
        $this->container['country'] = $country;

        return $this;
    }

    /**
     * Gets source_id
     *
     * @return string
     */
    public function getSourceId()
    {
        return $this->container['source_id'];
    }

    /**
     * Sets source_id
     *
     * @param string $source_id source_id
     *
     * @return self
     */
    public function setSourceId($source_id)
    {
        if (is_null($source_id)) {
            throw new \InvalidArgumentException('non-nullable source_id cannot be null');
        }
        $this->container['source_id'] = $source_id;

        return $this;
    }

    /**
     * Gets page_rank
     *
     * @return int
     */
    public function getPageRank()
    {
        return $this->container['page_rank'];
    }

    /**
     * Sets page_rank
     *
     * @param int $page_rank page_rank
     *
     * @return self
     */
    public function setPageRank($page_rank)
    {
        if (is_null($page_rank)) {
            throw new \InvalidArgumentException('non-nullable page_rank cannot be null');
        }
        $this->container['page_rank'] = $page_rank;

        return $this;
    }

    /**
     * Gets domain_url
     *
     * @return string
     */
    public function getDomainUrl()
    {
        return $this->container['domain_url'];
    }

    /**
     * Sets domain_url
     *
     * @param string $domain_url domain_url
     *
     * @return self
     */
    public function setDomainUrl($domain_url)
    {
        if (is_null($domain_url)) {
            throw new \InvalidArgumentException('non-nullable domain_url cannot be null');
        }
        $this->container['domain_url'] = $domain_url;

        return $this;
    }

    /**
     * Gets eng_title
     *
     * @return string
     */
    public function getEngTitle()
    {
        return $this->container['eng_title'];
    }

    /**
     * Sets eng_title
     *
     * @param string $eng_title eng_title
     *
     * @return self
     */
    public function setEngTitle($eng_title)
    {
        if (is_null($eng_title)) {
            throw new \InvalidArgumentException('non-nullable eng_title cannot be null');
        }
        $this->container['eng_title'] = $eng_title;

        return $this;
    }

    /**
     * Gets entities
     *
     * @return \AskNews\Model\Entities
     */
    public function getEntities()
    {
        return $this->container['entities'];
    }

    /**
     * Sets entities
     *
     * @param \AskNews\Model\Entities $entities entities
     *
     * @return self
     */
    public function setEntities($entities)
    {
        if (is_null($entities)) {
            throw new \InvalidArgumentException('non-nullable entities cannot be null');
        }
        $this->container['entities'] = $entities;

        return $this;
    }

    /**
     * Gets image_url
     *
     * @return string|null
     */
    public function getImageUrl()
    {
        return $this->container['image_url'];
    }

    /**
     * Sets image_url
     *
     * @param string|null $image_url image_url
     *
     * @return self
     */
    public function setImageUrl($image_url)
    {
        if (is_null($image_url)) {
            array_push($this->openAPINullablesSetToNull, 'image_url');
        } else {
            $nullablesSetToNull = $this->getOpenAPINullablesSetToNull();
            $index = array_search('image_url', $nullablesSetToNull);
            if ($index !== FALSE) {
                unset($nullablesSetToNull[$index]);
                $this->setOpenAPINullablesSetToNull($nullablesSetToNull);
            }
        }
        $this->container['image_url'] = $image_url;

        return $this;
    }

    /**
     * Gets keywords
     *
     * @return string[]
     */
    public function getKeywords()
    {
        return $this->container['keywords'];
    }

    /**
     * Sets keywords
     *
     * @param string[] $keywords keywords
     *
     * @return self
     */
    public function setKeywords($keywords)
    {
        if (is_null($keywords)) {
            throw new \InvalidArgumentException('non-nullable keywords cannot be null');
        }
        $this->container['keywords'] = $keywords;

        return $this;
    }

    /**
     * Gets language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->container['language'];
    }

    /**
     * Sets language
     *
     * @param string $language language
     *
     * @return self
     */
    public function setLanguage($language)
    {
        if (is_null($language)) {
            throw new \InvalidArgumentException('non-nullable language cannot be null');
        }
        $this->container['language'] = $language;

        return $this;
    }

    /**
     * Gets pub_date
     *
     * @return \DateTime
     */
    public function getPubDate()
    {
        return $this->container['pub_date'];
    }

    /**
     * Sets pub_date
     *
     * @param \DateTime $pub_date pub_date
     *
     * @return self
     */
    public function setPubDate($pub_date)
    {
        if (is_null($pub_date)) {
            throw new \InvalidArgumentException('non-nullable pub_date cannot be null');
        }
        $this->container['pub_date'] = $pub_date;

        return $this;
    }

    /**
     * Gets summary
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->container['summary'];
    }

    /**
     * Sets summary
     *
     * @param string $summary summary
     *
     * @return self
     */
    public function setSummary($summary)
    {
        if (is_null($summary)) {
            throw new \InvalidArgumentException('non-nullable summary cannot be null');
        }
        $this->container['summary'] = $summary;

        return $this;
    }

    /**
     * Gets title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->container['title'];
    }

    /**
     * Sets title
     *
     * @param string $title title
     *
     * @return self
     */
    public function setTitle($title)
    {
        if (is_null($title)) {
            throw new \InvalidArgumentException('non-nullable title cannot be null');
        }
        $this->container['title'] = $title;

        return $this;
    }

    /**
     * Gets sentiment
     *
     * @return int
     */
    public function getSentiment()
    {
        return $this->container['sentiment'];
    }

    /**
     * Sets sentiment
     *
     * @param int $sentiment sentiment
     *
     * @return self
     */
    public function setSentiment($sentiment)
    {
        if (is_null($sentiment)) {
            throw new \InvalidArgumentException('non-nullable sentiment cannot be null');
        }
        $this->container['sentiment'] = $sentiment;

        return $this;
    }

    /**
     * Gets centroid_distance
     *
     * @return float
     */
    public function getCentroidDistance()
    {
        return $this->container['centroid_distance'];
    }

    /**
     * Sets centroid_distance
     *
     * @param float $centroid_distance centroid_distance
     *
     * @return self
     */
    public function setCentroidDistance($centroid_distance)
    {
        if (is_null($centroid_distance)) {
            throw new \InvalidArgumentException('non-nullable centroid_distance cannot be null');
        }
        $this->container['centroid_distance'] = $centroid_distance;

        return $this;
    }

    /**
     * Gets cluster_probability
     *
     * @return float
     */
    public function getClusterProbability()
    {
        return $this->container['cluster_probability'];
    }

    /**
     * Sets cluster_probability
     *
     * @param float $cluster_probability cluster_probability
     *
     * @return self
     */
    public function setClusterProbability($cluster_probability)
    {
        if (is_null($cluster_probability)) {
            throw new \InvalidArgumentException('non-nullable cluster_probability cannot be null');
        }
        $this->container['cluster_probability'] = $cluster_probability;

        return $this;
    }

    /**
     * Gets markdown_citation
     *
     * @return string|null
     */
    public function getMarkdownCitation()
    {
        return $this->container['markdown_citation'];
    }

    /**
     * Sets markdown_citation
     *
     * @param string|null $markdown_citation markdown_citation
     *
     * @return self
     */
    public function setMarkdownCitation($markdown_citation)
    {
        if (is_null($markdown_citation)) {
            throw new \InvalidArgumentException('non-nullable markdown_citation cannot be null');
        }
        $this->container['markdown_citation'] = $markdown_citation;

        return $this;
    }

    /**
     * Gets provocative
     *
     * @return string|null
     */
    public function getProvocative()
    {
        return $this->container['provocative'];
    }

    /**
     * Sets provocative
     *
     * @param string|null $provocative provocative
     *
     * @return self
     */
    public function setProvocative($provocative)
    {
        if (is_null($provocative)) {
            throw new \InvalidArgumentException('non-nullable provocative cannot be null');
        }
        $allowedValues = $this->getProvocativeAllowableValues();
        if (!in_array($provocative, $allowedValues, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    "Invalid value '%s' for 'provocative', must be one of '%s'",
                    $provocative,
                    implode("', '", $allowedValues)
                )
            );
        }
        $this->container['provocative'] = $provocative;

        return $this;
    }

    /**
     * Gets reporting_voice
     *
     * @return \AskNews\Model\ReportingVoice1|null
     */
    public function getReportingVoice()
    {
        return $this->container['reporting_voice'];
    }

    /**
     * Sets reporting_voice
     *
     * @param \AskNews\Model\ReportingVoice1|null $reporting_voice reporting_voice
     *
     * @return self
     */
    public function setReportingVoice($reporting_voice)
    {
        if (is_null($reporting_voice)) {
            throw new \InvalidArgumentException('non-nullable reporting_voice cannot be null');
        }
        $this->container['reporting_voice'] = $reporting_voice;

        return $this;
    }

    /**
     * Gets entity_relation_graph
     *
     * @return \AskNews\Model\AsknewsApiSchemaV1CommonGraphRelationships|null
     */
    public function getEntityRelationGraph()
    {
        return $this->container['entity_relation_graph'];
    }

    /**
     * Sets entity_relation_graph
     *
     * @param \AskNews\Model\AsknewsApiSchemaV1CommonGraphRelationships|null $entity_relation_graph entity_relation_graph
     *
     * @return self
     */
    public function setEntityRelationGraph($entity_relation_graph)
    {
        if (is_null($entity_relation_graph)) {
            array_push($this->openAPINullablesSetToNull, 'entity_relation_graph');
        } else {
            $nullablesSetToNull = $this->getOpenAPINullablesSetToNull();
            $index = array_search('entity_relation_graph', $nullablesSetToNull);
            if ($index !== FALSE) {
                unset($nullablesSetToNull[$index]);
                $this->setOpenAPINullablesSetToNull($nullablesSetToNull);
            }
        }
        $this->container['entity_relation_graph'] = $entity_relation_graph;

        return $this;
    }

    /**
     * Gets geo_coordinates
     *
     * @return array<string,\AskNews\Model\GeoCoordinate>|null
     */
    public function getGeoCoordinates()
    {
        return $this->container['geo_coordinates'];
    }

    /**
     * Sets geo_coordinates
     *
     * @param array<string,\AskNews\Model\GeoCoordinate>|null $geo_coordinates geo_coordinates
     *
     * @return self
     */
    public function setGeoCoordinates($geo_coordinates)
    {
        if (is_null($geo_coordinates)) {
            array_push($this->openAPINullablesSetToNull, 'geo_coordinates');
        } else {
            $nullablesSetToNull = $this->getOpenAPINullablesSetToNull();
            $index = array_search('geo_coordinates', $nullablesSetToNull);
            if ($index !== FALSE) {
                unset($nullablesSetToNull[$index]);
                $this->setOpenAPINullablesSetToNull($nullablesSetToNull);
            }
        }
        $this->container['geo_coordinates'] = $geo_coordinates;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     *
     * @param integer $offset Offset
     *
     * @return boolean
     */
    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     *
     * @param integer $offset Offset
     *
     * @return mixed|null
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->container[$offset] ?? null;
    }

    /**
     * Sets value based on offset.
     *
     * @param int|null $offset Offset
     * @param mixed    $value  Value to be set
     *
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     *
     * @param integer $offset Offset
     *
     * @return void
     */
    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     * @link https://www.php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return mixed Returns data which can be serialized by json_encode(), which is a value
     * of any type other than a resource.
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
       return ObjectSerializer::sanitizeForSerialization($this);
    }

    /**
     * Gets the string presentation of the object
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode(
            ObjectSerializer::sanitizeForSerialization($this),
            JSON_PRETTY_PRINT
        );
    }

    /**
     * Gets a header-safe presentation of the object
     *
     * @return string
     */
    public function toHeaderValue()
    {
        return json_encode(ObjectSerializer::sanitizeForSerialization($this));
    }
}


