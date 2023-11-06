<?php

namespace ThreePHP\Paths;

class Earcut
{

    public static function triangulate($data, $holeIndices, $dim = 2)
    {
        $hasHoles = $holeIndices && count($holeIndices) > 0;
        $outerLen = $hasHoles ? $holeIndices[0] * $dim : count($data);
        $outerNode = static::linkedList($data, 0, $outerLen, $dim, true);
        $triangles = [];

        if (!$outerNode || $outerNode->next === $outerNode->prev) {
            return $triangles;
        }

        $minX = $minY = $maxX = $maxY = $x = $y = 0;
        $invSize = 0;

        if ($hasHoles) {
            $outerNode = static::eliminateHoles($data, $holeIndices, $outerNode, $dim);
        }

        if (count($data) > 80 * $dim) {
            $minX = $maxX = $data[0];
            $minY = $maxY = $data[1];

            for ($i = $dim; $i < $outerLen; $i += $dim) {
                $x = $data[$i];
                $y = $data[$i + 1];

                if ($x < $minX) $minX = $x;
                if ($y < $minY) $minY = $y;
                if ($x > $maxX) $maxX = $x;
                if ($y > $maxY) $maxY = $y;
            }

            $invSize = max($maxX - $minX, $maxY - $minY);
            $invSize = ($invSize !== 0) ? 32767 / $invSize : 0;
        }

        static::earcutLinked($outerNode, $triangles, $dim, $minX, $minY, $invSize, 0);

        return $triangles;
    }

    private static function linkedList($data, $start, $end, $dim, $clockwise)
    {
        $i = 0;
        $last = null;

        if ($clockwise === (static::signedArea($data, $start, $end, $dim) > 0)) {
            for ($i = $start; $i < $end; $i += $dim) {
                $last = static::insertNode($i, $data[$i], $data[$i + 1], $last);
            }
        } else {
            for ($i = $end - $dim; $i >= $start; $i -= $dim) {
                $last = static::insertNode($i, $data[$i], $data[$i + 1], $last);
            }
        }

        if ($last && static::equals($last, $last->next)) {
            static::removeNode($last);
            $last = $last->next;
        }

        return $last;
    }

    private static function filterPoints($start, $end = null)
    {
        if (!$start) {
            return $start;
        }

        if (!$end) {
            $end = $start;
        }

        $p = $start;
        $again = false;

        do {
            $again = false;

            if (!$p->steiner && (static::equals($p, $p->next) || static::area($p->prev, $p, $p->next) === 0)) {
                static::removeNode($p);
                $p = $end = $p->prev;

                if ($p === $p->next) {
                    break;
                }

                $again = true;
            } else {
                $p = $p->next;
            }
        } while ($again || $p !== $end);

        return $end;
    }

    private static function earcutLinked($ear, &$triangles, $dim, $minX, $minY, $invSize, $pass)
    {
        if (!$ear) {
            return;
        }

        if (!$pass && $invSize) {
            static::indexCurve($ear, $minX, $minY, $invSize);
        }

        $stop = $ear;
        $prev = null;
        $next = null;

        while ($ear->prev !== $ear->next) {
            $prev = $ear->prev;
            $next = $ear->next;

            if ($invSize ? static::isEarHashed($ear, $minX, $minY, $invSize) : static::isEar($ear)) {
                $triangles[] = (int)($prev->i / $dim);
                $triangles[] = (int)($ear->i / $dim);
                $triangles[] = (int)($next->i / $dim);
                static::removeNode($ear);
                $ear = $next->next;
                $stop = $next->next;
                continue;
            }

            $ear = $next;

            if ($ear === $stop) {
                if (!$pass) {
                    static::earcutLinked(static::filterPoints($ear), $triangles, $dim, $minX, $minY, $invSize, 1);
                } elseif ($pass === 1) {
                    $ear = static::cureLocalIntersections(static::filterPoints($ear), $triangles, $dim);
                    static::earcutLinked($ear, $triangles, $dim, $minX, $minY, $invSize, 2);
                } elseif ($pass === 2) {
                    static::splitEarcut($ear, $triangles, $dim, $minX, $minY, $invSize);
                }

                break;
            }
        }
    }

    private static function isEar($ear)
    {
        $a = $ear->prev;
        $b = $ear;
        $c = $ear->next;

        if (static::area($a, $b, $c) >= 0) {
            return false; // reflex, can't be an ear
        }

        $ax = $a->x;
        $bx = $b->x;
        $cx = $c->x;
        $ay = $a->y;
        $by = $b->y;
        $cy = $c->y;

        $x0 = min($ax, $bx, $cx);
        $y0 = min($ay, $by, $cy);
        $x1 = max($ax, $bx, $cx);
        $y1 = max($ay, $by, $cy);

        $p = $c->next;

        while ($p !== $a) {
            if (
                $p->x >= $x0 && $p->x <= $x1 && $p->y >= $y0 && $p->y <= $y1 &&
                static::pointInTriangle($ax, $ay, $bx, $by, $cx, $cy, $p->x, $p->y) &&
                static::area($p->prev, $p, $p->next) >= 0
            ) {
                return false;
            }

            $p = $p->next;
        }

        return true;
    }

    private static function isEarHashed($ear, $minX, $minY, $invSize)
    {
        $a = $ear->prev;
        $b = $ear;
        $c = $ear->next;

        if (static::area($a, $b, $c) >= 0) {
            return false; // reflex, can't be an ear
        }

        $ax = $a->x;
        $bx = $b->x;
        $cx = $c->x;
        $ay = $a->y;
        $by = $b->y;
        $cy = $c->y;

        $x0 = min($ax, $bx, $cx);
        $y0 = min($ay, $by, $cy);
        $x1 = max($ax, $bx, $cx);
        $y1 = max($ay, $by, $cy);

        $minZ = static::zOrder($x0, $y0, $minX, $minY, $invSize);
        $maxZ = static::zOrder($x1, $y1, $minX, $minY, $invSize);

        $p = $ear->prevZ;
        $n = $ear->nextZ;

        while ($p && $p->z >= $minZ && $n && $n->z <= $maxZ) {
            if (
                $p->x >= $x0 && $p->x <= $x1 && $p->y >= $y0 && $p->y <= $y1 &&
                $p !== $a && $p !== $c &&
                static::pointInTriangle($ax, $ay, $bx, $by, $cx, $cy, $p->x, $p->y) &&
                static::area($p->prev, $p, $p->next) >= 0
            ) {
                return false;
            }

            $p = $p->prevZ;

            if (
                $n->x >= $x0 && $n->x <= $x1 && $n->y >= $y0 && $n->y <= $y1 &&
                $n !== $a && $n !== $c &&
                static::pointInTriangle($ax, $ay, $bx, $by, $cx, $cy, $n->x, $n->y) &&
                static::area($n->prev, $n, $n->next) >= 0
            ) {
                return false;
            }

            $n = $n->nextZ;
        }

        while ($p && $p->z >= $minZ) {
            if (
                $p->x >= $x0 && $p->x <= $x1 && $p->y >= $y0 && $p->y <= $y1 &&
                $p !== $a && $p !== $c &&
                static::pointInTriangle($ax, $ay, $bx, $by, $cx, $cy, $p->x, $p->y) &&
                static::area($p->prev, $p, $p->next) >= 0
            ) {
                return false;
            }

            $p = $p->prevZ;
        }

        while ($n && $n->z <= $maxZ) {
            if (
                $n->x >= $x0 && $n->x <= $x1 && $n->y >= $y0 && $n->y <= $y1 &&
                $n !== $a && $n !== $c &&
                static::pointInTriangle($ax, $ay, $bx, $by, $cx, $cy, $n->x, $n->y) &&
                static::area($n->prev, $n, $n->next) >= 0
            ) {
                return false;
            }

            $n = $n->nextZ;
        }

        return true;
    }
    private static function cureLocalIntersections($start, $triangles, $dim)
    {
        $p = $start;
        do {
            $a = $p->prev;
            $b = $p->next->next;
            if (!static::equals($a, $b) && static::intersects($a, $p, $p->next, $b) && static::locallyInside($a, $b) && static::locallyInside($b, $a)) {
                $triangles[] = (int)($a->i / $dim);
                $triangles[] = (int)($p->i / $dim);
                $triangles[] = (int)($b->i / $dim);
                // remove two nodes involved
                static::removeNode($p);
                static::removeNode($p->next);
                $p = $start = $b;
            }
            $p = $p->next;
        } while ($p !== $start);
        return static::filterPoints($p);
    }

    // try splitting polygon into two and triangulate them independently
    private static function splitEarcut($start, $triangles, $dim, $minX, $minY, $invSize)
    {
        // look for a valid diagonal that divides the polygon into two
        $a = $start;
        do {
            $b = $a->next->next;
            while ($b !== $a->prev) {
                if ($a->i !== $b->i && static::isValidDiagonal($a, $b)) {
                    // split the polygon in two by the diagonal
                    $c = static::splitPolygon($a, $b);
                    // filter colinear points around the cuts
                    $a = static::filterPoints($a, $a->next);
                    $c = static::filterPoints($c, $c->next);
                    // run earcut on each half
                    static::earcutLinked($a, $triangles, $dim, $minX, $minY, $invSize, 0);
                    static::earcutLinked($c, $triangles, $dim, $minX, $minY, $invSize, 0);
                    return;
                }
                $b = $b->next;
            }
            $a = $a->next;
        } while ($a !== $start);
    }

    // link every hole into the outer loop, producing a single-ring polygon without holes
    private static function eliminateHoles($data, $holeIndices, $outerNode, $dim)
    {
        $queue = [];
        $len = count($holeIndices);
        $i = $start = $end = 0;
        for ($i = 0; $i < $len; $i++) {
            $start = $holeIndices[$i] * $dim;
            $end = $i < $len - 1 ? $holeIndices[$i + 1] * $dim : count($data);
            /** @var Node $list */
            $list = static::linkedList($data, $start, $end, $dim, false);
            if ($list === $list->next) {
                $list->steiner = true;
            }
            $queue[] = static::getLeftmost($list);
        }
        usort($queue, function ($a, $b) {
            return $a->x - $b->x;
        });
        // process holes from left to right
        for ($i = 0; $i < count($queue); $i++) {
            $outerNode = static::eliminateHole($queue[$i], $outerNode);
        }
        return $outerNode;
    }

    // private static function compareX($a, $b)
    // {
    //     return $a->x - $b->x;
    // }

    // find a bridge between vertices that connects hole with an outer ring and link it
    private static function eliminateHole($hole, $outerNode)
    {
        $bridge = static::findHoleBridge($hole, $outerNode);
        if (!$bridge) {
            return $outerNode;
        }
        $bridgeReverse = static::splitPolygon($bridge, $hole);
        // filter collinear points around the cuts
        static::filterPoints($bridgeReverse, $bridgeReverse->next);
        return static::filterPoints($bridge, $bridge->next);
    }

    // David Eberly's algorithm for finding a bridge between hole and outer polygon
    private static function findHoleBridge($hole, $outerNode)
    {
        $p = $outerNode;
        $qx = -INF;
        $m = null;
        $hx = $hole->x;
        $hy = $hole->y;
        // find a segment intersected by a ray from the hole's leftmost point to the left;
        // segment's endpoint with lesser x will be potential connection point
        do {
            if ($hy <= $p->y && $hy >= $p->next->y && $p->next->y !== $p->y) {
                $x = $p->x + ($hy - $p->y) * ($p->next->x - $p->x) / ($p->next->y - $p->y);
                if ($x <= $hx && $x > $qx) {
                    $qx = $x;
                    $m = $p->x < $p->next->x ? $p : $p->next;
                    if ($x === $hx) return $m; // hole touches outer segment; pick leftmost endpoint
                }
            }
            $p = $p->next;
        } while ($p !== $outerNode);
        if (!$m) return null;
        // look for points inside the triangle of hole point, segment intersection, and endpoint;
        // if there are no points found, we have a valid connection;
        // otherwise, choose the point of the minimum angle with the ray as the connection point
        $stop = $m;
        $mx = $m->x;
        $my = $m->y;
        $tanMin = INF;
        $tan = 0;
        $p = $m;
        do {
            if (
                $hx >= $p->x && $p->x >= $mx && $hx !== $p->x &&
                static::pointInTriangle($hy < $my ? $hx : $qx, $hy, $mx, $my, $hy < $my ? $qx : $hx, $hy, $p->x, $p->y)
            ) {
                $tan = abs($hy - $p->y) / ($hx - $p->x); // tangential
                if (static::locallyInside($p, $hole) && ($tan < $tanMin || ($tan === $tanMin && ($p->x > $m->x || ($p->x === $m->x && static::sectorContainsSector($m, $p)))))) {
                    $m = $p;
                    $tanMin = $tan;
                }
            }
            $p = $p->next;
        } while ($p !== $stop);
        return $m;
    }

    // whether sector in vertex m contains sector in vertex p in the same coordinates
    private static function sectorContainsSector($m, $p)
    {
        return static::area($m->prev, $m, $p->prev) < 0 && static::area($p->next, $m, $m->next) < 0;
    }

    // interlink polygon nodes in z-order
    private static function indexCurve($start, $minX, $minY, $invSize)
    {
        $p = $start;
        do {
            if ($p->z === 0) $p->z = static::zOrder($p->x, $p->y, $minX, $minY, $invSize);
            $p->prevZ = $p->prev;
            $p->nextZ = $p->next;
            $p = $p->next;
        } while ($p !== $start);
        $p->prevZ->nextZ = null;
        $p->prevZ = null;
        static::sortLinked($p);
    }

    private static function sortLinked($list)
    {
        $i = $p = $q = $e = $tail = $numMerges = $pSize = $qSize = 0;
        $inSize = 1;

        do {
            $p = $list;
            $list = $tail = null;
            $numMerges = 0;

            while ($p) {
                $numMerges++;
                $q = $p;
                $pSize = 0;

                for ($i = 0; $i < $inSize; $i++) {
                    $pSize++;
                    $q = $q->nextZ;

                    if (!$q) break;
                }

                $qSize = $inSize;

                while ($pSize > 0 || ($qSize > 0 && $q)) {
                    if ($pSize !== 0 && ($qSize === 0 || !$q || $p->z <= $q->z)) {
                        $e = $p;
                        $p = $p->nextZ;
                        $pSize--;
                    } else {
                        $e = $q;
                        $q = $q->nextZ;
                        $qSize--;
                    }

                    if ($tail) {
                        $tail->nextZ = $e;
                    } else {
                        $list = $e;
                    }

                    $e->prevZ = $tail;
                    $tail = $e;
                }

                $p = $q;
            }

            $tail->nextZ = null;
            $inSize *= 2;
        } while ($numMerges > 1);

        return $list;
    }

    private static function zOrder($x, $y, $minX, $minY, $invSize)
    {
        $x = intval(($x - $minX) * $invSize);
        $y = intval(($y - $minY) * $invSize);
        $x = ($x | ($x << 8)) & 0x00FF00FF;
        $x = ($x | ($x << 4)) & 0x0F0F0F0F;
        $x = ($x | ($x << 2)) & 0x33333333;
        $x = ($x | ($x << 1)) & 0x55555555;
        $y = ($y | ($y << 8)) & 0x00FF00FF;
        $y = ($y | ($y << 4)) & 0x0F0F0F0F;
        $y = ($y | ($y << 2)) & 0x33333333;
        $y = ($y | ($y << 1)) & 0x55555555;
        return $x | ($y << 1);
    }

    private static function getLeftmost($start)
    {
        $p = $start;
        $leftmost = $start;

        do {
            if ($p->x < $leftmost->x || ($p->x === $leftmost->x && $p->y < $leftmost->y)) {
                $leftmost = $p;
            }

            $p = $p->next;
        } while ($p !== $start);

        return $leftmost;
    }

    private static function pointInTriangle($ax, $ay, $bx, $by, $cx, $cy, $px, $py)
    {
        return ($cx - $px) * ($ay - $py) >= ($ax - $px) * ($cy - $py) &&
            ($ax - $px) * ($by - $py) >= ($bx - $px) * ($ay - $py) &&
            ($bx - $px) * ($cy - $py) >= ($cx - $px) * ($by - $py);
    }

    private static function isValidDiagonal($a, $b)
    {
        return $a->next->i !== $b->i && $a->prev->i !== $b->i && !static::intersectsPolygon($a, $b) &&
            (static::locallyInside($a, $b) && static::locallyInside($b, $a) && static::middleInside($a, $b) ||
                ($a === $b && static::area($a->prev, $a, $a->next) > 0 && static::area($b->prev, $b, $b->next) > 0));
    }

    private static function area($p, $q, $r)
    {
        return ($q->y - $p->y) * ($r->x - $q->x) - ($q->x - $p->x) * ($r->y - $q->y);
    }

    private static function equals($p1, $p2)
    {
        return $p1->x === $p2->x && $p1->y === $p2->y;
    }

    private static function intersects($p1, $q1, $p2, $q2)
    {
        $o1 = static::sign(static::area($p1, $q1, $p2));
        $o2 = static::sign(static::area($p1, $q1, $q2));
        $o3 = static::sign(static::area($p2, $q2, $p1));
        $o4 = static::sign(static::area($p2, $q2, $q1));

        if ($o1 !== $o2 && $o3 !== $o4) return true;

        if ($o1 === 0 && static::onSegment($p1, $p2, $q1)) return true;
        if ($o2 === 0 && static::onSegment($p1, $q2, $q1)) return true;
        if ($o3 === 0 && static::onSegment($p2, $p1, $q2)) return true;
        if ($o4 === 0 && static::onSegment($p2, $q1, $q2)) return true;

        return false;
    }
    // Funzione per verificare se il punto q giace sul segmento pr
    private static function onSegment($p, $q, $r)
    {
        return $q->x <= max($p->x, $r->x) && $q->x >= min($p->x, $r->x) && $q->y <= max($p->y, $r->y) && $q->y >= min($p->y, $r->y);
    }

    // Funzione per determinare il segno di un numero
    private static function sign($num)
    {
        return $num > 0 ? 1 : ($num < 0 ? -1 : 0);
    }

    // Funzione per verificare se una diagonale del poligono interseca qualche segmento del poligono
    private static function intersectsPolygon($a, $b)
    {
        $p = $a;
        do {
            if (
                $p->i !== $a->i && $p->next->i !== $a->i && $p->i !== $b->i && $p->next->i !== $b->i &&
                static::intersects($p, $p->next, $a, $b)
            ) {
                return true;
            }
            $p = $p->next;
        } while ($p !== $a);
        return false;
    }

    // Funzione per verificare se una diagonale del poligono è localmente all'interno del poligono
    private static function locallyInside($a, $b)
    {
        return static::area($a->prev, $a, $a->next) < 0 ?
            (static::area($a, $b, $a->next) >= 0 && static::area($a, $a->prev, $b) >= 0) : (static::area($a, $b, $a->prev) < 0 || static::area($a, $a->next, $b) < 0);
    }

    // Funzione per verificare se il punto medio di una diagonale del poligono è all'interno del poligono
    private static function middleInside($a, $b)
    {
        $p = $a;
        $inside = false;
        $px = ($a->x + $b->x) / 2;
        $py = ($a->y + $b->y) / 2;
        do {
            if (($p->y > $py !== $p->next->y > $py) && $p->next->y !== $p->y &&
                ($px < ($p->next->x - $p->x) * ($py - $p->y) / ($p->next->y - $p->y) + $p->x)
            ) {
                $inside = !$inside;
            }
            $p = $p->next;
        } while ($p !== $a);
        return $inside;
    }

    // Funzione per collegare due vertici del poligono con un ponte; se i vertici appartengono allo stesso anello, suddivide il poligono in due;
    // se uno appartiene all'anello esterno e l'altro a un foro, li unisce in un singolo anello
    private static function splitPolygon($a, $b)
    {
        $a2 = new Node($a->i, $a->x, $a->y);
        $b2 = new Node($b->i, $b->x, $b->y);
        $an = $a->next;
        $bp = $b->prev;
        $a->next = $b;
        $b->prev = $a;
        $a2->next = $an;
        $an->prev = $a2;
        $b2->next = $a2;
        $a2->prev = $b2;
        $bp->next = $b2;
        $b2->prev = $bp;
        return $b2;
    }

    // Funzione per inserire un nodo e collegarlo eventualmente al nodo precedente (in una lista circolare doppiamente collegata)
    private static function insertNode($i, $x, $y, $last = null)
    {
        $p = new Node($i, $x, $y);
        if (!$last) {
            $p->prev = $p;
            $p->next = $p;
        } else {
            $p->next = $last->next;
            $p->prev = $last;
            $last->next->prev = $p;
            $last->next = $p;
        }
        return $p;
    }

    private static function removeNode($p)
    {
        $p->next->prev = $p->prev;
        $p->prev->next = $p->next;
        if ($p->prevZ) {
            $p->prevZ->nextZ = $p->nextZ;
        }
        if ($p->nextZ) {
            $p->nextZ->prevZ = $p->prevZ;
        }
    }

    private static function signedArea($data, $start, $end, $dim)
    {
        $sum = 0;
        for ($i = $start, $j = $end - $dim; $i < $end; $i += $dim) {
            $sum += ($data[$j] - $data[$i]) * ($data[$i + 1] + $data[$j + 1]);
            $j = $i;
        }
        return $sum;
    }
}
