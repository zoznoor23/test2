from fastapi import FastAPI
from fastapi.responses import HTMLResponse
from app.api.v1.routes import router as api_router

app = FastAPI()

@app.get("/", response_class=HTMLResponse)
async def read_root():
    with open("app/static/tweaky.html", "r", encoding="utf-8") as f:
        return f.read()

app.include_router(api_router, prefix="/api/v1")