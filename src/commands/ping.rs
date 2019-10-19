use crate::{locale_args, Locale, get_help};
use serenity::client::Context;
use serenity::framework::standard::{
    macros::{command, group},
    Args, CommandResult,
};
use serenity::model::channel::Message;

group!({
    name: "ping",
    options: {},
    commands: [ping],
});

#[command]
fn ping(ctx: &mut Context, msg: &Message, args: Args) -> CommandResult {
    get_help!("ping", ctx, msg, args);
    msg.channel_id.say(
        &ctx.http,
        Locale::single(
            "main",
            "pong",
            Some(&locale_args! {
                "message" => args.message()
            }),
            None,
        )
        .unwrap_or("pong".into()),
    )?;
    Ok(())
}
