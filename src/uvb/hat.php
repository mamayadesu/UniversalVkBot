<?php

/**
 * Эта функция используется для поддержания работы асинхронных задач. Пожалуйста, используйте эту функцию в коде своих плагинов, если выполнение такого кода (например, циклы с обработкой большого объёма данных) занимает продолжительное время. Достаточно просто добавить в некоторых местах "\hat();"
 *
 * @return void
 */
function hat() : void
{
    \uvb\Services\RamController::GetInstance()->Check();
    if (\uvb\Bot::GetInstance() === null)
        return;
    \uvb\Bot::GetInstance()->HandleAsyncTasksWhenProcessIsBusy();
}