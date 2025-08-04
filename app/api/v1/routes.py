from fastapi import APIRouter
from app.api.v1 import imageproxy, healthcheck, bot

router = APIRouter()

router.include_router(healthcheck.router, prefix="", tags=["HealthCheck"])
router.include_router(imageproxy.router, prefix="", tags=["ImageProxy"])
router.include_router(bot.router, prefix="", tags=["Bot"])