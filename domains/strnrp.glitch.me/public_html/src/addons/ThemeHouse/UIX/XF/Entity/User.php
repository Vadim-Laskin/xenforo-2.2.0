<?php

namespace ThemeHouse\UIX\XF\Entity;

/**
 * Class User
 * @package ThemeHouse\UIX\XF\Entity
 */
class User extends XFCP_User
{
    /**
     * @var array
     */
    protected $thuixWatchedNodes;

    /**
     * @return array
     */
    public function getThUixWatchedForums()
    {
        if (!$this->user_id) {
            return [];
        }

        if (!$this->thuixWatchedNodes) {

            $watchedNodeIds = \XF::db()->fetchAllColumn('
                SELECT node_id
                FROM xf_forum_watch
                WHERE user_id = ?
            ', [$this->user_id]);
            if (empty($watchedNodeIds)) {
                $this->thuixWatchedNodes = [];
            } else {

                /** @var \XF\Repository\Node $nodeRepo */
                $nodeRepo = $this->repository('XF:Node');
                $nodeTree = $nodeRepo->createNodeTree($nodeRepo->getNodeList());
                $nodeExtra = $nodeRepo->getNodeListExtras($nodeTree);

                $nodes = $nodeTree->getFlattened();
                $watchedNodes = [];

                foreach ($nodes as $nodeId => $node) {
                    /** @var \XF\Entity\Node $nodeEntity */
                    $nodeEntity = $node['record'];
                    if ($nodeEntity->node_type_id != 'Forum') {
                        continue;
                    }

                    if (!in_array($nodeId, $watchedNodeIds)) {
                        continue;
                    }

                    $node['extra'] = $nodeExtra[$nodeId];
                    $watchedNodes[] = $node;
                }

                $this->thuixWatchedNodes = $watchedNodes;
            }
        }

        return $this->thuixWatchedNodes;
    }

    /**
     * @param int $limit
     * @param string $order
     * @param string $direction
     * @return \XF\Mvc\Entity\ArrayCollection
     */
    public function getThUixWatchedThreads($limit = 5, $order = 'last_post_date', $direction = 'ASC')
    {
        if (!$this->user_id) {
            return $this->em()->getEmptyCollection();
        }

        /** @var \XF\Repository\Thread $threadRepo */
        $threadRepo = $this->repository('XF:Thread');
        $threadFinder = $threadRepo->findThreadsForWatchedList();
        $threadFinder->order($order, $direction);
        return $threadFinder->fetch($limit > 0 ? $limit : null);
    }
}
