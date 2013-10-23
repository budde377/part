<?php
require_once dirname(__FILE__) . '/CBPageContentNodeImpl.php';
/**
 * Created by PhpStorm.
 * User: budde
 * Date: 10/23/13
 * Time: 9:13 PM
 */

class CBPageContentTokenParserImpl extends Twig_TokenParser
{

    private $currentPage;

    function __construct(Page $currentPage)
    {
        $this->currentPage = $currentPage;
    }


    /**
     * Parses a token and returns a node.
     *
     * @param Twig_Token $token A Twig_Token instance
     *
     * @return Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        if($stream->getCurrent()->getType() == Twig_Token::BLOCK_END_TYPE){
            $stream->expect(Twig_Token::BLOCK_END_TYPE);
            return new CBPageContentNodeImpl($this->currentPage, $token->getLine(), $this->getTag());
        }
        $name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);
        return new CBPageContentNodeImpl($this->currentPage, $token->getLine(), $this->getTag(), $name);
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return "page_content";
    }
}