<?php

namespace HtmlDiff;

abstract class Action
{
    const Equal = 0;
    const Delete = 1;
    const Insert = 2;
    const None = 3;
    const Replace = 4;
}