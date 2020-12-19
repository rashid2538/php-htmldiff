<?php

namespace HtmlDiff;

abstract class Mode
{
    const Character = 0;
    const Tag = 1;
    const Whitespace = 2;
    const Entity = 3;
}
