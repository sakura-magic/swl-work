<?php
namespace server\agreement;
use server\other\ManageLink;

interface FpmTaskInterface
{
    /**
     * FpmTaskInterface constructor.
     * @param ManageLink $manageLink
     */
   public function __construct(ManageLink $manageLink);

    /**
     * 开始
     * @return int
     */
   public function start():int;
}